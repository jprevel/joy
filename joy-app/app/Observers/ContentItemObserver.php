<?php

namespace App\Observers;

use App\Jobs\SendContentApprovedNotification;
use App\Models\ContentItem;

class ContentItemObserver
{
    /**
     * Handle the ContentItem "updated" event.
     *
     * Dispatch notification when content is approved by client
     *
     * Note: No "rejected" status exists - client feedback is handled via comments
     * which trigger notifications through CommentObserver
     */
    public function updated(ContentItem $contentItem): void
    {
        // Check if client has Slack integration
        if (!$contentItem->client?->hasSlackIntegration()) {
            return;
        }

        // Check if status changed to approved
        if (!$contentItem->isDirty('status')) {
            return;
        }

        // Dispatch approval notification
        if ($contentItem->status === 'approved') {
            SendContentApprovedNotification::dispatch($contentItem);
        }
    }
}
