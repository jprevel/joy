<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContentItemStoreRequest;
use App\Http\Requests\ContentItemUpdateRequest;
use App\Http\Requests\ContentItemUpdateStatusRequest;
use App\Http\Traits\ApiResponse;
use App\Models\ContentItem;
use App\Services\ContentItemService;
use App\Services\RoleDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ContentItemController extends Controller
{
    use ApiResponse;
    public function __construct(
        private ContentItemService $contentItemService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Display a listing of content items.
     */
    public function index(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        $filters = $request->only(['status', 'platform', 'from_date', 'to_date', 'view']);
        $contentItems = $this->contentItemService->getForClient($client, $filters);

        return $this->success([
            'data' => $contentItems,
            'meta' => [
                'client_id' => $client->id,
                'client_name' => $client->name,
                'filters_applied' => $filters,
            ]
        ]);
    }

    /**
     * Store a newly created content item.
     */
    public function store(ContentItemStoreRequest $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $validatedData = $request->validated();
            $contentItem = $this->contentItemService->create($validatedData, $user);

            return $this->created(
                $this->contentItemService->formatForApi($contentItem),
                'Content item created successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create content item', $e);
        }
    }

    /**
     * Display the specified content item.
     */
    public function show(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        return $this->success($this->contentItemService->formatForApi($contentItem));
    }

    /**
     * Update the specified content item.
     */
    public function update(ContentItemUpdateRequest $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        // Only agency users can update content
        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $validatedData = $request->validated();
            $updatedContentItem = $this->contentItemService->update($contentItem, $validatedData, $user);

            return $this->success(
                $this->contentItemService->formatForApi($updatedContentItem),
                'Content item updated successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update content item', $e);
        }
    }

    /**
     * Update content item status.
     */
    public function updateStatus(ContentItemUpdateStatusRequest $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        try {
            $validatedData = $request->validated();
            $newStatus = $validatedData['status'];

            // Only agency can move to draft/review, clients can approve
            if (in_array($newStatus, ['draft', 'review', 'scheduled']) && !$this->roleDetectionService->isAgency($user)) {
                return $this->forbidden();
            }

            if ($newStatus === 'approved' && !$this->roleDetectionService->hasPermission($user, 'approve_content')) {
                return $this->forbidden();
            }

            $updatedContentItem = $this->contentItemService->updateStatus($contentItem, $newStatus, $user);

            return $this->success(
                $this->contentItemService->formatForApi($updatedContentItem),
                'Content status updated successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors(), 'Invalid status transition');
        } catch (\Exception $e) {
            return $this->serverError('Failed to update status', $e);
        }
    }

    /**
     * Remove the specified content item.
     */
    public function destroy(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        // Only agency users can delete content
        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $deleted = $this->contentItemService->delete($contentItem, $user);

            if ($deleted) {
                return $this->deleted('Content item deleted successfully');
            } else {
                return $this->serverError('Failed to delete content item');
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete content item', $e);
        }
    }

    /**
     * Get available statuses for content item transitions.
     */
    public function availableStatuses(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        $availableStatuses = [];
        $currentStatus = $contentItem->status;

        // Define status transitions based on user role
        if ($this->roleDetectionService->isAgency($user)) {
            $availableStatuses = match($currentStatus) {
                'draft' => ['review', 'scheduled'],
                'review' => ['draft', 'approved', 'scheduled'],
                'approved' => ['review', 'scheduled'],
                'scheduled' => ['review'],
                default => []
            };
        } elseif ($this->roleDetectionService->isClient($user)) {
            $availableStatuses = match($currentStatus) {
                'review' => ['approved'],
                default => []
            };
        }

        return $this->success([
            'current_status' => $currentStatus,
            'available_statuses' => $availableStatuses
        ]);
    }

    /**
     * Get content item statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        // User and client resolved by middleware
        $client = $request->get('resolved_client');

        $stats = [
            'total' => $client->contentItems()->count(),
            'by_status' => $client->contentItems()->selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status'),
            'by_platform' => $client->contentItems()->selectRaw('platform, count(*) as count')->groupBy('platform')->pluck('count', 'platform'),
            'scheduled_this_month' => $client->contentItems()->whereMonth('scheduled_at', now()->month)->count(),
            'pending_approval' => $client->contentItems()->where('status', 'review')->count(),
        ];

        return $this->success([
            'stats' => $stats,
            'client_id' => $client->id
        ]);
    }
}