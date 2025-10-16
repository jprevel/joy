<?php

namespace App\Contracts;

use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\ClientStatusUpdate;

/**
 * Interface SlackNotificationServiceContract
 *
 * Defines the contract for business logic around sending Slack notifications
 * for different Joy application events. This service orchestrates the creation
 * and sending of notifications based on user actions.
 *
 * @package App\Contracts
 */
interface SlackNotificationServiceContract
{
    /**
     * Send a notification when a client adds a comment to content.
     *
     * @param Comment $comment The comment that was created
     * @return array{success: bool, notification_id?: int, error?: string}
     */
    public function sendClientCommentNotification(Comment $comment): array;

    /**
     * Send a notification when a client approves a content item.
     *
     * @param ContentItem $contentItem The content item that was approved
     * @return array{success: bool, notification_id?: int, error?: string}
     */
    public function sendContentApprovedNotification(ContentItem $contentItem): array;

    /**
     * Send a notification when a client rejects a content item.
     *
     * @param ContentItem $contentItem The content item that was rejected
     * @return array{success: bool, notification_id?: int, error?: string}
     */
    public function sendContentRejectedNotification(ContentItem $contentItem): array;

    /**
     * Send a notification when an account manager submits a Statusfaction report.
     *
     * @param ClientStatusUpdate $statusUpdate The status update that was submitted
     * @return array{success: bool, notification_id?: int, error?: string}
     */
    public function sendStatusfactionSubmittedNotification(ClientStatusUpdate $statusUpdate): array;

    /**
     * Send a notification when an admin approves a Statusfaction report.
     *
     * @param ClientStatusUpdate $statusUpdate The status update that was approved
     * @return array{success: bool, notification_id?: int, error?: string}
     */
    public function sendStatusfactionApprovedNotification(ClientStatusUpdate $statusUpdate): array;

    /**
     * Check if a client has Slack notifications enabled.
     *
     * @param int $clientId Client ID
     * @return bool
     */
    public function isEnabledForClient(int $clientId): bool;

    /**
     * Get Slack channel ID for a given client.
     *
     * @param int $clientId Client ID
     * @return string|null Channel ID or null if not configured
     */
    public function getClientChannelId(int $clientId): ?string;
}
