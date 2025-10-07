<?php

namespace App\Livewire;

use App\Models\ContentItem;
use App\Models\User;
use App\Services\ContentItemService;
use App\Services\CommentService;
use App\Services\RoleDetectionService;
use Livewire\Component;

class ContentDetail extends Component
{
    public ?ContentItem $contentItem = null;
    public ?User $currentUser = null;
    public string $newComment = '';

    public function __construct(
        private ContentItemService $contentItemService,
        private CommentService $commentService,
        private RoleDetectionService $roleDetectionService
    ) {
        parent::__construct();
    }

    public function mount(ContentItem $contentItem)
    {
        $this->contentItem = $contentItem;
        $this->currentUser = $this->roleDetectionService->getCurrentUser();

        // Check if user has access to this content item
        if (!$this->hasAccess()) {
            abort(403, 'Access denied');
        }

        // Load relationships
        $this->contentItem->load(['client', 'user', 'comments.user']);
    }

    public function hasAccess(): bool
    {
        if (!$this->currentUser || !$this->contentItem) {
            return false;
        }

        // Admin can access everything
        if ($this->roleDetectionService->isAdmin($this->currentUser)) {
            return true;
        }

        // Agency can access clients they manage
        if ($this->roleDetectionService->isAgency($this->currentUser)) {
            $accessibleClients = $this->roleDetectionService->getAccessibleClients($this->currentUser);
            return $accessibleClients->contains('id', $this->contentItem->client_id);
        }

        // Clients can only access their own content
        return $this->currentUser->client_id === $this->contentItem->client_id;
    }

    public function addComment()
    {
        if (empty(trim($this->newComment))) {
            $this->addError('newComment', 'Please enter a comment.');
            return;
        }

        try {
            $this->commentService->create([
                'content_item_id' => $this->contentItem->id,
                'body' => trim($this->newComment),
            ], $this->currentUser);

            $this->newComment = '';

            // Reload the content item with comments
            $this->contentItem->load(['comments.user']);

            session()->flash('success', 'Comment added successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to add comment: ' . $e->getMessage());
        }
    }

    public function updateStatus(string $status)
    {
        try {
            $this->contentItemService->updateStatus(
                $this->contentItem->id,
                $status,
                $this->currentUser
            );

            $this->contentItem->refresh();

            session()->flash('success', 'Status updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    public function hasPermission(string $permission): bool
    {
        if (!$this->currentUser) {
            return false;
        }

        return $this->roleDetectionService->hasPermission($this->currentUser, $permission);
    }

    public function render()
    {
        return view('livewire.content-detail')
            ->layout('components.layouts.app');
    }
}