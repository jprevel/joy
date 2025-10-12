<?php

namespace App\Services;

use App\Contracts\SlackBlockFormatterContract;
use App\Models\ClientStatusfactionUpdate;
use App\Models\Comment;
use App\Models\ContentItem;

class SlackBlockFormatter implements SlackBlockFormatterContract
{
    /**
     * Format client comment notification
     *
     * CRITICAL: Link directs to specific content item (clarification #4 from spec.md)
     */
    public function formatClientComment(Comment $comment): array
    {
        $contentItem = $comment->contentItem;
        $client = $contentItem->client;

        // Generate link to specific content item detail page
        $contentLink = url("/content-items/{$contentItem->id}");

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '💬 New Comment from Client',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Author:*\n{$this->escapeText($comment->author_name)}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Content:*\n{$this->escapeText($contentItem->title)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Platform:*\n" . ($contentItem->platform ?? 'N/A'),
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Comment:*\n{$this->escapeText($comment->body)}",
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<{$contentLink}|View Content Item in Joy>",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Commented {$this->formatTimestamp($comment->created_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format content approved notification
     */
    public function formatContentApproved(ContentItem $contentItem): array
    {
        $client = $contentItem->client;
        $contentLink = url("/content-items/{$contentItem->id}");

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '✅ Content Approved',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Status:*\nApproved",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Content:*\n{$this->escapeText($contentItem->title)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Platform:*\n" . ($contentItem->platform ?? 'N/A'),
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "<{$contentLink}|View Content Item in Joy>",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Approved {$this->formatTimestamp($contentItem->updated_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format statusfaction submitted notification
     *
     * CRITICAL: DOES NOT include client satisfaction or team health scores (FR-019, FR-020 from spec.md)
     */
    public function formatStatusfactionSubmitted(ClientStatusfactionUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;
        $user = $statusUpdate->user;
        $team = $client->team;

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '📊 Statusfaction Report Submitted',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Team:*\n" . $this->escapeText($team->name ?? 'N/A'),
                    ],
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Account Manager:*\n{$this->escapeText($user->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Week:*\n{$statusUpdate->week_start_date->format('M d, Y')}",
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Status Notes:*\n{$this->escapeText($statusUpdate->status_notes)}",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Submitted {$this->formatTimestamp($statusUpdate->created_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Format statusfaction approved notification
     */
    public function formatStatusfactionApproved(ClientStatusfactionUpdate $statusUpdate): array
    {
        $client = $statusUpdate->client;
        $approver = $statusUpdate->approver;

        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => '✅ Statusfaction Report Approved',
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Client:*\n{$this->escapeText($client->name)}",
                    ],
                    [
                        'type' => 'mrkdwn',
                        'text' => "*Approved by:*\n" . $this->escapeText($approver->name ?? 'Admin'),
                    ],
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*Week:*\n{$statusUpdate->week_start_date->format('M d, Y')}",
                ],
            ],
            [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "Approved {$this->formatTimestamp($statusUpdate->approved_at)}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Escape text for Slack markdown
     */
    public function escapeText(string $text): string
    {
        // Escape special Slack markdown characters
        return str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $text);
    }

    /**
     * Format timestamp for human-readable display
     */
    public function formatTimestamp(\DateTimeInterface $timestamp): string
    {
        return $timestamp->diffForHumans();
    }

    /**
     * Create a link button for Slack
     */
    public function createLinkButton(string $url, string $text): array
    {
        return [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => $text,
                    ],
                    'url' => $url,
                ],
            ],
        ];
    }
}
