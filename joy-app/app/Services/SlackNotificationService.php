<?php

namespace App\Services;

use App\Contracts\SlackNotificationServiceContract;
use App\Contracts\SlackServiceContract;
use App\Contracts\SlackBlockFormatterContract;
use App\Models\ClientStatusfactionUpdate;
use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\SlackNotification;
use App\Models\SlackWorkspace;
use Illuminate\Support\Facades\Log;

class SlackNotificationService implements SlackNotificationServiceContract
{
    public function __construct(
        protected SlackServiceContract $slackService,
        protected SlackBlockFormatterContract $formatter
    ) {}

    /**
     * Send client comment notification
     */
    public function sendClientCommentNotification(Comment $comment): array
    {
        $client = $comment->contentItem->client;

        if (!$client->hasSlackIntegration()) {
            Log::info('Client does not have Slack integration', ['client_id' => $client->id]);
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            Log::warning('No Slack workspace configured');
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        // Create audit record (pending)
        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'client_comment',
            'notifiable_type' => Comment::class,
            'notifiable_id' => $comment->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            // Format message
            $blocks = $this->formatter->formatClientComment($comment);

            // Send to Slack
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "New comment from {$comment->author_name}"
            );

            // Update notification record
            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
                Log::info('Slack notification sent successfully', [
                    'notification_id' => $notification->id,
                    'type' => 'client_comment',
                ]);
            } else {
                // NO RETRY per clarification #2 from spec.md - just log and mark as failed
                $notification->markAsFailed($result['error']);
                Log::warning('Slack notification failed', [
                    'notification_id' => $notification->id,
                    'error' => $result['error'],
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error('Slack notification exception', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send content approved notification
     */
    public function sendContentApprovedNotification(ContentItem $contentItem): array
    {
        $client = $contentItem->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'content_approved',
            'notifiable_type' => ContentItem::class,
            'notifiable_id' => $contentItem->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatContentApproved($contentItem);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "Content approved: {$contentItem->title}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send statusfaction submitted notification
     *
     * CRITICAL: NO notifications when editing pending reports (clarification #1 from spec.md)
     * Only sent on initial submission
     */
    public function sendStatusfactionSubmittedNotification(ClientStatusfactionUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'statusfaction_submitted',
            'notifiable_type' => ClientStatusfactionUpdate::class,
            'notifiable_id' => $statusUpdate->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatStatusfactionSubmitted($statusUpdate);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "New statusfaction report for {$client->name}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send statusfaction approved notification
     */
    public function sendStatusfactionApprovedNotification(ClientStatusfactionUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;

        if (!$client->hasSlackIntegration()) {
            return ['success' => false, 'error' => 'Client does not have Slack integration'];
        }

        $workspace = SlackWorkspace::getDefault();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        $notification = SlackNotification::create([
            'workspace_id' => $workspace->id,
            'type' => 'statusfaction_approved',
            'notifiable_type' => ClientStatusfactionUpdate::class,
            'notifiable_id' => $statusUpdate->id,
            'channel_id' => $client->slack_channel_id,
            'channel_name' => $client->slack_channel_name,
            'status' => 'pending',
        ]);

        try {
            $blocks = $this->formatter->formatStatusfactionApproved($statusUpdate);
            $result = $this->slackService->postMessage(
                $client->slack_channel_id,
                $blocks,
                "Statusfaction report approved for {$client->name}"
            );

            $notification->update(['payload' => $blocks]);

            if ($result['success']) {
                $notification->markAsSent($result);
            } else {
                $notification->markAsFailed($result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Check if Slack is enabled for a client
     */
    public function isEnabledForClient(int $clientId): bool
    {
        $client = \App\Models\Client::find($clientId);
        return $client?->hasSlackIntegration() ?? false;
    }

    /**
     * Get Slack channel ID for a client
     */
    public function getClientChannelId(int $clientId): ?string
    {
        $client = \App\Models\Client::find($clientId);
        return $client?->slack_channel_id;
    }
}
