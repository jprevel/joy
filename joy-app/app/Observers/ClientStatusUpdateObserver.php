<?php

namespace App\Observers;

use App\Jobs\SendStatusfactionApprovedNotification;
use App\Jobs\SendStatusfactionSubmittedNotification;
use App\Models\ClientStatusfactionUpdate;

class ClientStatusUpdateObserver
{
    /**
     * Handle the ClientStatusfactionUpdate "created" event.
     *
     * CRITICAL: Only send notification on INITIAL submission (clarification #1 from spec.md)
     * Do NOT send notifications on edits to pending reports
     */
    public function created(ClientStatusfactionUpdate $statusUpdate): void
    {
        // Check if client has Slack integration
        if (!$statusUpdate->client?->hasSlackIntegration()) {
            return;
        }

        // Dispatch submission notification
        SendStatusfactionSubmittedNotification::dispatch($statusUpdate);
    }

    /**
     * Handle the ClientStatusfactionUpdate "updated" event.
     *
     * Only dispatch approval notification when approval_status changes to 'approved'
     *
     * CRITICAL: Do NOT send submission notifications on edits (clarification #1 from spec.md)
     */
    public function updated(ClientStatusfactionUpdate $statusUpdate): void
    {
        // Check if client has Slack integration
        if (!$statusUpdate->client?->hasSlackIntegration()) {
            return;
        }

        // Check if approval_status changed to 'approved'
        if ($statusUpdate->isDirty('approval_status') && $statusUpdate->approval_status === 'approved') {
            SendStatusfactionApprovedNotification::dispatch($statusUpdate);
        }

        // NOTE: Do NOT dispatch submission notification on edits
        // This prevents duplicate notifications when AM edits pending report
    }
}
