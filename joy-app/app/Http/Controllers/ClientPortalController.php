<?php

namespace App\Http\Controllers;
use App\Http\Traits\ApiResponse;

use App\Http\Requests\CommentStoreRequest;
use App\Models\MagicLink;
use App\Models\ContentItem;
use App\Models\Comment;
use App\Services\MagicLinkService;
use App\Services\ContentItemService;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ClientPortalController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MagicLinkService $magicLinkService,
        private ContentItemService $contentItemService,
        private CommentService $commentService
    ) {}

    /**
     * Access client portal via magic link.
     */
    public function access(Request $request, string $token): JsonResponse
    {
        try {
            $request->validate([
                'pin' => 'sometimes|nullable|string|size:4|regex:/^\d{4}$/'
            ]);

            $pin = $request->input('pin');
            $magicLink = $this->magicLinkService->validateAccess($token, $pin);

            return $this->success([
                'token' => $magicLink->token,
                'client' => [
                    'id' => $magicLink->client->id,
                    'name' => $magicLink->client->name,
                ],
                'scopes' => $magicLink->scopes,
                'expires_at' => $magicLink->expires_at->toISOString(),
                'available_routes' => $this->getAvailableRoutes($magicLink)
            ], 'Access granted to client portal');

        } catch (ValidationException $e) {
            return $this->unauthorized('Access denied');
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid or expired magic link');
        }
    }

    /**
     * Get client dashboard data.
     */
    public function dashboard(Request $request, string $token): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'view');
            $client = $magicLink->client;

            // Get dashboard statistics
            $stats = [
                'total_content' => $client->contentItems()->count(),
                'pending_review' => $client->contentItems()->where('status', 'review')->count(),
                'approved_content' => $client->contentItems()->where('status', 'approved')->count(),
                'scheduled_content' => $client->contentItems()->where('status', 'scheduled')->count(),
                'this_month' => $client->contentItems()->whereMonth('created_at', now()->month)->count(),
                'upcoming_this_week' => $client->contentItems()
                    ->whereBetween('scheduled_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->count(),
            ];

            // Get recent activity
            $recentContent = $this->contentItemService->getForClient($client, [
                'view' => 'timeline'
            ])->take(5);

            return $this->success([
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                ],
                'stats' => $stats,
                'recent_content' => $recentContent,
                'access_info' => [
                    'scopes' => $magicLink->scopes,
                    'expires_at' => $magicLink->expires_at->toISOString(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load dashboard', $e);
        }
    }

    /**
     * Get client content calendar.
     */
    public function calendar(Request $request, string $token): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'view');
            $client = $magicLink->client;

            $request->validate([
                'month' => 'sometimes|date_format:Y-m',
                'view' => 'sometimes|in:grid,timeline'
            ]);

            $month = $request->input('month', now()->format('Y-m'));
            $view = $request->input('view', 'grid');

            // Create date range
            $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $endDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->endOfMonth();

            $filters = [
                'from_date' => $startDate->toDateString(),
                'to_date' => $endDate->toDateString(),
                'view' => $view
            ];

            $contentItems = $this->contentItemService->getForClient($client, $filters);

            return $this->success([
                'content_items' => $contentItems,
                'calendar_info' => [
                    'month' => $month,
                    'view' => $view,
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'total_items' => $contentItems->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load calendar', $e);
        }
    }

    /**
     * Get specific content item for client view.
     */
    public function contentItem(Request $request, string $token, int $contentItemId): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'view');
            $client = $magicLink->client;

            $contentItem = ContentItem::where('client_id', $client->id)->findOrFail($contentItemId);

            // Get comments for this content item (external only for clients)
            $comments = $this->commentService->getForClientView($contentItem);

            return $this->success([
                'content_item' => $this->contentItemService->formatForApi($contentItem),
                'comments' => $comments,
                'can_comment' => $this->magicLinkService->hasScope($magicLink, 'comment'),
                'can_approve' => $this->magicLinkService->hasScope($magicLink, 'approve') && $contentItem->status === 'review',
                'comment_stats' => $this->commentService->getStats($contentItem)
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load content item', $e);
        }
    }

    /**
     * Add comment to content item via magic link.
     */
    public function addComment(CommentStoreRequest $request, string $token, int $contentItemId): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'comment');
            $client = $magicLink->client;

            $contentItem = ContentItem::where('client_id', $client->id)->findOrFail($contentItemId);

            if (!$this->commentService->canComment($contentItem)) {
                return $this->forbidden('Comments not allowed on this content');
            }

            $validatedData = $request->validated();
            $comment = $this->commentService->addClientComment($contentItem, $validatedData);

            return $this->created(
                $this->commentService->formatForApi($comment),
                'Comment added successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to add comment', $e);
        }
    }

    /**
     * Approve content item via magic link.
     */
    public function approveContent(Request $request, string $token, int $contentItemId): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'approve');
            $client = $magicLink->client;

            $contentItem = ContentItem::where('client_id', $client->id)->findOrFail($contentItemId);

            if ($contentItem->status !== 'review') {
                return $this->validationError(['status' => 'Content item is not in review status']);
            }

            $updatedContentItem = $this->contentItemService->updateStatus($contentItem, 'approved');

            return $this->success(
                $this->contentItemService->formatForApi($updatedContentItem),
                'Content approved successfully'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to approve content', $e);
        }
    }

    /**
     * Get content items pending client review.
     */
    public function pendingReview(Request $request, string $token): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'view');
            $client = $magicLink->client;

            $pendingContent = $this->contentItemService->getForClient($client, [
                'status' => 'review'
            ]);

            return $this->success([
                'content_items' => $pendingContent,
                'total_pending' => $pendingContent->count(),
                'can_approve' => $this->magicLinkService->hasScope($magicLink, 'approve')
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load pending content', $e);
        }
    }

    /**
     * Get client activity feed.
     */
    public function activity(Request $request, string $token): JsonResponse
    {
        try {
            $magicLink = $this->validateMagicLinkAccess($token, 'view');
            $client = $magicLink->client;

            $request->validate([
                'limit' => 'sometimes|integer|min:1|max:100'
            ]);

            $limit = $request->input('limit', 20);

            // Get recent content activity for this client
            $recentContent = $client->contentItems()
                ->with(['user', 'comments' => function ($q) {
                    $q->where('is_internal', false)->latest()->limit(1);
                }])
                ->latest()
                ->limit($limit)
                ->get();

            $activity = $recentContent->map(function ($item) {
                return [
                    'type' => 'content',
                    'content_item' => $this->contentItemService->formatForApi($item),
                    'latest_comment' => $item->comments->first() ?
                        $this->commentService->formatForApi($item->comments->first()) : null,
                    'timestamp' => $item->updated_at->toISOString()
                ];
            });

            return $this->success([
                'data' => $activity,
                'meta' => [
                    'limit' => $limit,
                    'total_shown' => $activity->count()
                ]
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load activity', $e);
        }
    }

    /**
     * Validate magic link access and required scope.
     */
    private function validateMagicLinkAccess(string $token, string $requiredScope = null): MagicLink
    {
        $magicLink = $this->magicLinkService->findValidByToken($token);

        if (!$magicLink) {
            throw new \Exception('Invalid or expired magic link', 401);
        }

        if ($requiredScope && !$this->magicLinkService->hasScope($magicLink, $requiredScope)) {
            throw new \Exception('Insufficient permissions', 403);
        }

        return $magicLink;
    }

    /**
     * Get available routes based on magic link scopes.
     */
    private function getAvailableRoutes(MagicLink $magicLink): array
    {
        $routes = [];

        if ($this->magicLinkService->hasScope($magicLink, 'view')) {
            $routes[] = 'dashboard';
            $routes[] = 'calendar';
            $routes[] = 'content';
            $routes[] = 'activity';
        }

        if ($this->magicLinkService->hasScope($magicLink, 'comment')) {
            $routes[] = 'comments';
        }

        if ($this->magicLinkService->hasScope($magicLink, 'approve')) {
            $routes[] = 'approve';
            $routes[] = 'pending-review';
        }

        return $routes;
    }

    /**
     * Get magic link info.
     */
    public function linkInfo(string $token): JsonResponse
    {
        try {
            $magicLink = $this->magicLinkService->findByToken($token);

            if (!$magicLink) {
                return $this->notFound('Magic link not found');
            }

            return $this->success([
                'token' => $magicLink->token,
                'client_name' => $magicLink->client->name,
                'scopes' => $magicLink->scopes,
                'expires_at' => $magicLink->expires_at->toISOString(),
                'requires_pin' => $magicLink->requiresPin(),
                'is_expired' => $magicLink->isExpired(),
                'available_scopes' => $this->magicLinkService->getAvailableScopes()
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to load link info', $e);
        }
    }
}