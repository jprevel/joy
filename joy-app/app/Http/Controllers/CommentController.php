<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Comment;
use App\Models\ContentItem;
use App\Services\CommentService;
use App\Services\RoleDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    use ApiResponse;
    public function __construct(
        private CommentService $commentService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Display comments for a content item.
     */
    public function index(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        // Determine which comments to show based on user role
        if ($this->roleDetectionService->isAgency($user)) {
            $comments = $this->commentService->getForAgencyView($contentItem);
        } else {
            $comments = $this->commentService->getForClientView($contentItem);
        }

        return $this->success([
            'data' => $comments,
            'meta' => [
                'content_item_id' => $contentItem->id,
                'can_comment' => $this->commentService->canComment($contentItem, $user),
                'stats' => $this->commentService->getStats($contentItem)
            ]
        ]);
    }

    /**
     * Store a new comment.
     */
    public function store(CommentStoreRequest $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        if (!$this->commentService->canComment($contentItem, $user)) {
            return $this->forbidden('Comments not allowed on this content');
        }

        try {
            $validatedData = $request->validated();

            // Determine comment type based on user role and request
            if ($this->roleDetectionService->isAgency($user)) {
                // Agency users can create internal or external comments
                $isInternal = $request->boolean('is_internal', false);
                if ($isInternal) {
                    $comment = $this->commentService->addAgencyComment($contentItem, $validatedData, $user);
                } else {
                    $comment = $this->commentService->create($contentItem, array_merge($validatedData, [
                        'is_internal' => false
                    ]), $user);
                }
            } else {
                // Client users always create external comments
                $comment = $this->commentService->addClientComment($contentItem, $validatedData);
            }

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
     * Display the specified comment.
     */
    public function show(Request $request, ContentItem $contentItem, Comment $comment): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        // Check if comment belongs to the content item
        if ($comment->content_item_id !== $contentItem->id) {
            return $this->notFound('Comment not found');
        }

        // Check if user can see this comment (internal comments only visible to agency)
        if ($comment->is_internal && !$this->roleDetectionService->isAgency($user)) {
            return $this->notFound('Comment not found');
        }

        return $this->success(
            $this->commentService->formatForApi($comment)
        );
    }

    /**
     * Update the specified comment.
     */
    public function update(CommentUpdateRequest $request, ContentItem $contentItem, Comment $comment): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        if ($this->commentDoesNotBelongToContentItem($comment, $contentItem)) {
            return $this->notFound('Comment not found');
        }

        if ($this->userCannotUpdateComment($user, $comment)) {
            return $this->forbidden();
        }

        try {
            $validatedData = $request->validated();
            $updatedComment = $this->commentService->update($comment, $validatedData, $user);

            return $this->success(
                $this->commentService->formatForApi($updatedComment),
                'Comment updated successfully'
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update comment', $e);
        }
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, ContentItem $contentItem, Comment $comment): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        if ($this->commentDoesNotBelongToContentItem($comment, $contentItem)) {
            return $this->notFound('Comment not found');
        }

        if ($this->userCannotDeleteComment($user, $comment)) {
            return $this->forbidden();
        }

        try {
            $deleted = $this->commentService->delete($comment, $user);

            if ($this->commentWasDeleted($deleted)) {
                return $this->deleted('Comment deleted successfully');
            } else {
                return $this->serverError('Failed to delete comment');
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete comment', $e);
        }
    }

    /**
     * Get comment statistics for a content item.
     */
    public function stats(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        $stats = $this->commentService->getStats($contentItem);

        return $this->success([
            'data' => $stats,
            'content_item_id' => $contentItem->id
        ]);
    }

    /**
     * Check if user can comment on content item.
     */
    public function canComment(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        $canComment = $this->commentService->canComment($contentItem, $user);

        return $this->success([
            'can_comment' => $canComment,
            'content_item_id' => $contentItem->id,
            'content_status' => $contentItem->status
        ]);
    }

    /**
     * Bulk operations on comments.
     */
    public function bulkAction(Request $request, ContentItem $contentItem): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
            return $this->forbidden();
        }

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'action' => 'required|string|in:delete',
                'comment_ids' => 'required|array|min:1',
                'comment_ids.*' => 'required|integer|exists:comments,id'
            ]);

            $action = $request->input('action');
            $commentIds = $request->input('comment_ids');

            // Verify all comments belong to this content item and user can modify them
            $comments = Comment::whereIn('id', $commentIds)
                ->where('content_item_id', $contentItem->id)
                ->get();

            if ($this->notAllCommentsWereFound($comments, $commentIds)) {
                return $this->notFound('Some comments not found');
            }

            $results = ['success' => 0, 'failed' => 0, 'errors' => []];

            foreach ($comments as $comment) {
                if ($this->userCannotModifyComment($user, $comment)) {
                    $results['failed']++;
                    $results['errors'][] = "Cannot modify comment {$comment->id}";
                    continue;
                }

                try {
                    if ($action === 'delete') {
                        $this->commentService->delete($comment, $user);
                        $results['success']++;
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Failed to {$action} comment {$comment->id}: {$e->getMessage()}";
                }
            }

            return $this->success(
                ['results' => $results],
                "Bulk {$action} completed"
            );

        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Bulk operation failed', $e);
        }
    }

    /**
     * Check if comment does not belong to the content item.
     */
    private function commentDoesNotBelongToContentItem(Comment $comment, ContentItem $contentItem): bool
    {
        return $comment->content_item_id !== $contentItem->id;
    }

    /**
     * Check if user cannot update the comment.
     * Only agency users can update comments, and only their own.
     */
    private function userCannotUpdateComment($user, Comment $comment): bool
    {
        return !$this->roleDetectionService->isAgency($user)
            || ($comment->user_id && $comment->user_id !== $user->id);
    }

    /**
     * Check if user cannot delete the comment.
     * Only agency users can delete comments, and only their own.
     */
    private function userCannotDeleteComment($user, Comment $comment): bool
    {
        return !$this->roleDetectionService->isAgency($user)
            || ($comment->user_id && $comment->user_id !== $user->id);
    }

    /**
     * Check if comment was successfully deleted.
     */
    private function commentWasDeleted(bool $deleted): bool
    {
        return $deleted === true;
    }

    /**
     * Check if not all requested comments were found.
     */
    private function notAllCommentsWereFound($comments, array $commentIds): bool
    {
        return $comments->count() !== count($commentIds);
    }

    /**
     * Check if user cannot modify the comment (for bulk operations).
     * Non-admin users can only modify their own comments.
     */
    private function userCannotModifyComment($user, Comment $comment): bool
    {
        return !$this->roleDetectionService->isAdmin($user)
            && $comment->user_id
            && $comment->user_id !== $user->id;
    }
}