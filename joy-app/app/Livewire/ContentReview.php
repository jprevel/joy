<?php

namespace App\Livewire;

use App\Models\ContentItem;
use App\Models\Client;
use App\Models\Comment;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class ContentReview extends Component
{
    public string $date;
    public Carbon $reviewDate;
    public Collection $contentItems;
    public ?Client $client = null;
    public array $commentText = [];
    public string $currentRole = 'client'; // For testing: client, agency, admin

    public function mount(string $date)
    {
        $this->date = $date;
        
        try {
            $this->reviewDate = Carbon::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            abort(404, 'Invalid date format');
        }
        
        // For demo purposes, get the first client
        // In production, this would be determined by magic link token
        $this->client = Client::first();
        
        $this->loadContentItems();
    }

    public function loadContentItems()
    {
        if (!$this->client) {
            $this->contentItems = collect();
            return;
        }

        // Get all content items for the specific date (all statuses for review)
        $this->contentItems = $this->client->contentItems()
            ->with('comments') // Load comments with the content items
            ->whereDate('scheduled_at', $this->reviewDate)
            ->orderBy('scheduled_at') // Chronological order
            ->get();
    }

    public function approveContent($itemId)
    {
        $contentItem = ContentItem::find($itemId);
        if ($contentItem && $contentItem->client_id === $this->client->id) {
            $contentItem->update(['status' => 'Approved']);
            
            // Add approval comment if there's text
            if (!empty($this->commentText[$itemId])) {
                $this->addComment($itemId);
            }
            
            $this->loadContentItems(); // Refresh the list
            
            session()->flash('success', "'{$contentItem->title}' has been approved!");
        }
    }

    public function requestChanges($itemId)
    {
        $contentItem = ContentItem::find($itemId);
        if ($contentItem && $contentItem->client_id === $this->client->id) {
            $contentItem->update(['status' => 'Changes Requested']);
            
            // Add comment if there's text (recommended for change requests)
            if (!empty($this->commentText[$itemId])) {
                $this->addComment($itemId);
            }
            
            $this->loadContentItems(); // Refresh the list
            
            session()->flash('info', "Changes requested for '{$contentItem->title}'. The agency team has been notified.");
        }
    }

    public function unapproveContent($itemId)
    {
        $contentItem = ContentItem::find($itemId);
        if ($contentItem && $contentItem->client_id === $this->client->id) {
            // Only allow unapproval of approved/scheduled content
            if (in_array($contentItem->status, ['Approved', 'Scheduled'])) {
                $contentItem->update(['status' => 'In Review']);
                
                // Add comment if there's text to explain unapproval
                if (!empty($this->commentText[$itemId])) {
                    $this->addComment($itemId);
                }
                
                $this->loadContentItems(); // Refresh the list
                
                session()->flash('info', "'{$contentItem->title}' has been returned to review status.");
            }
        }
    }

    public function addComment($itemId)
    {
        $contentItem = ContentItem::find($itemId);
        if (!$contentItem || $contentItem->client_id !== $this->client->id) {
            session()->flash('error', 'Content item not found or access denied.');
            return;
        }

        // Don't allow commenting on approved or scheduled content
        if (in_array($contentItem->status, ['Approved', 'Scheduled'])) {
            session()->flash('error', 'Cannot add comments to approved content. Please unapprove first if changes are needed.');
            return;
        }

        $comment = trim($this->commentText[$itemId] ?? '');
        if (empty($comment)) {
            session()->flash('error', 'Please enter a comment before submitting.');
            return;
        }

        // Save comment to database
        Comment::create([
            'content_item_id' => $itemId,
            'author_type' => 'client',
            'author_name' => $this->client->name,
            'body' => $comment,
        ]);
        
        // Clear the comment text
        $this->commentText[$itemId] = '';
        
        // Reload the content items to show the new comment
        $this->loadContentItems();
        
        
        // TODO: Send notification to agency team about new comment
    }

    public function switchRole($role)
    {
        $this->currentRole = $role;
        // Reload data based on role if needed
        $this->loadContentItems();
    }

    public function render()
    {
        return view('livewire.content-review')
            ->layout('components.layout');
    }
}