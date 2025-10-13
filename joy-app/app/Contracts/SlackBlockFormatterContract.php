<?php

namespace App\Contracts;

use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\ClientStatusfactionUpdate;

/**
 * Interface SlackBlockFormatterContract
 *
 * Defines the contract for formatting Joy application data into Slack Block Kit
 * message blocks. This handles the presentation layer for Slack notifications.
 *
 * @package App\Contracts
 */
interface SlackBlockFormatterContract
{
    /**
     * Format a client comment into Slack blocks.
     *
     * @param Comment $comment
     * @return array Array of Slack Block Kit blocks
     */
    public function formatClientComment(Comment $comment): array;

    /**
     * Format a content approval notification into Slack blocks.
     *
     * @param ContentItem $contentItem
     * @return array Array of Slack Block Kit blocks
     */
    public function formatContentApproved(ContentItem $contentItem): array;

    /**
     * Format a Statusfaction submission notification into Slack blocks.
     *
     * @param ClientStatusfactionUpdate $statusUpdate
     * @return array Array of Slack Block Kit blocks
     */
    public function formatStatusfactionSubmitted(ClientStatusfactionUpdate $statusUpdate): array;

    /**
     * Format a Statusfaction approval notification into Slack blocks.
     *
     * @param ClientStatusfactionUpdate $statusUpdate
     * @return array Array of Slack Block Kit blocks
     */
    public function formatStatusfactionApproved(ClientStatusfactionUpdate $statusUpdate): array;

    /**
     * Escape special characters for Slack's mrkdwn format.
     *
     * @param string $text
     * @return string Escaped text
     */
    public function escapeText(string $text): string;

    /**
     * Format a timestamp into a human-readable relative time.
     *
     * @param \DateTimeInterface $dateTime
     * @return string Formatted timestamp (e.g., "2 minutes ago")
     */
    public function formatTimestamp(\DateTimeInterface $dateTime): string;

    /**
     * Generate a link button block for navigating to Joy.
     *
     * @param string $url
     * @param string $text Button text
     * @return array Slack block for button
     */
    public function createLinkButton(string $url, string $text): array;
}
