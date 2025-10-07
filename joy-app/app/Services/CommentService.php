<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Support\Collection;

class CommentService
{
    public function __construct(
        private AuditService $auditService,
        private TrelloService $trelloService
    ) {}

    /**
     * Create a new comment on a content item.
     */
    public function create(ContentItem $contentItem, array $data, ?User $user = null): Comment
    {
        $comment = Comment::create([
            'content_item_id' => $contentItem->id,
            'user_id' => $user?->id,
            'author_name' => $data['author_name'] ?? $user?->name,
            'content' => $data['content'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);

        // Log comment creation
        $this->auditService->log([
            'user_id' => $user?->id,
            'client_id' => $contentItem->client_id,
            'event' => 'comment.created',
            'auditable_type' => Comment::class,
            'auditable_id' => $comment->id,
            'new_values' => [
                'content_item_id' => $contentItem->id,
                'author_name' => $comment->author_display_name,
                'content' => $comment->content,
                'is_internal' => $comment->is_internal,
            ],
        ]);

        // Sync to Trello if external comment
        if (!$comment->is_internal) {
            $this->trelloService->syncCommentToTrello($comment);
        }

        return $comment->fresh(['user', 'contentItem']);
    }

    /**
     * Update an existing comment.
     */
    public function update(Comment $comment, array $data, User $user): Comment
    {
        $oldValues = $comment->toArray();

        $updateData = array_filter([
            'content' => $data['content'] ?? null,
            'is_internal' => $data['is_internal'] ?? null,
        ], fn($value) => $value !== null);

        $comment->update($updateData);

        // Log comment update
        $this->auditService->log([
            'user_id' => $user->id,
            'client_id' => $comment->contentItem->client_id,
            'event' => 'comment.updated',
            'auditable_type' => Comment::class,
            'auditable_id' => $comment->id,
            'old_values' => $oldValues,
            'new_values' => $comment->fresh()->toArray(),
        ]);

        // Re-sync to Trello if needed
        if (!$comment->is_internal && isset($data['content'])) {
            $this->trelloService->syncCommentToTrello($comment);
        }

        return $comment->fresh(['user', 'contentItem']);
    }

    /**
     * Delete a comment.
     */
    public function delete(Comment $comment, User $user): bool
    {
        $oldValues = $comment->toArray();

        $deleted = $comment->delete();

        if ($deleted) {
            // Log comment deletion
            $this->auditService->log([
                'user_id' => $user->id,
                'client_id' => $comment->contentItem->client_id,
                'event' => 'comment.deleted',
                'auditable_type' => Comment::class,
                'auditable_id' => $comment->id,
                'old_values' => $oldValues,
            ]);

            // Remove from Trello if it was synced
            if ($comment->trelloCard) {
                $this->trelloService->removeCommentFromTrello($comment);
            }
        }

        return $deleted;
    }

    /**
     * Get comments for a content item.
     */
    public function getForContentItem(ContentItem $contentItem, bool $includeInternal = true): Collection
    {
        $query = $contentItem->comments()->with(['user']);

        if (!$includeInternal) {
            $query->clientVisible();
        }

        return $query->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($comment) => $this->formatForApi($comment));
    }

    /**
     * Get comments for client view (external only).
     */
    public function getForClientView(ContentItem $contentItem): Collection
    {
        return $this->getForContentItem($contentItem, false);
    }

    /**
     * Get comments for agency view (all comments).
     */
    public function getForAgencyView(ContentItem $contentItem): Collection
    {
        return $this->getForContentItem($contentItem, true);
    }

    /**
     * Add comment via magic link (client comment).
     */
    public function addClientComment(ContentItem $contentItem, array $data): Comment
    {
        return $this->create($contentItem, array_merge($data, [
            'is_internal' => false,
        ]));
    }

    /**
     * Add internal agency comment.
     */
    public function addAgencyComment(ContentItem $contentItem, array $data, User $user): Comment
    {
        return $this->create($contentItem, array_merge($data, [
            'is_internal' => true,
        ]), $user);
    }

    /**
     * Get comment statistics for a content item.
     */
    public function getStats(ContentItem $contentItem): array
    {
        $comments = $contentItem->comments;

        return [
            'total' => $comments->count(),
            'external' => $comments->where('is_internal', false)->count(),
            'internal' => $comments->where('is_internal', true)->count(),
            'from_client' => $comments->where('user_id', null)->count(),
            'from_agency' => $comments->whereNotNull('user_id')->count(),
        ];
    }

    /**
     * Check if user can comment on content item.
     */
    public function canComment(ContentItem $contentItem, ?User $user = null): bool
    {
        // Agency users can always comment
        if ($user) {
            return true;
        }

        // Client comments only allowed on reviewable content
        return $contentItem->can_comment;
    }

    /**
     * Format comment for API response.
     */
    public function formatForApi(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'content' => $comment->content,
            'author_name' => $comment->author_display_name,
            'is_internal' => $comment->is_internal,
            'is_from_client' => $comment->isFromClient(),
            'is_from_agency' => $comment->isFromAgency(),
            'created_at' => $comment->created_at->toISOString(),
            'updated_at' => $comment->updated_at->toISOString(),
        ];
    }

    /**
     * Get validation rules for comment creation.
     */
    public function getValidationRules(): array
    {
        return [
            'content' => 'required|string|max:2000',
            'author_name' => 'sometimes|nullable|string|max:255',
            'is_internal' => 'sometimes|boolean',
        ];
    }

    /**
     * Get validation rules for comment updates.
     */
    public function getUpdateValidationRules(): array
    {
        return [
            'content' => 'sometimes|required|string|max:2000',
            'is_internal' => 'sometimes|boolean',
        ];
    }
}