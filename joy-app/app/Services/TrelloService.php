<?php

namespace App\Services;

use App\Models\TrelloIntegration;
use App\Models\ContentItem;
use App\Models\Comment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class TrelloService
{
    private TrelloIntegration $integration;
    private string $baseUrl = 'https://api.trello.com/1';

    public function __construct(TrelloIntegration $integration)
    {
        $this->integration = $integration;
    }

    public function testConnection(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/members/me", [
                'key' => $this->integration->api_key,
                'token' => $this->integration->api_token,
            ]);

            if ($response->successful()) {
                $member = $response->json();
                
                $this->integration->markSyncCompleted([
                    'status' => 'completed',
                    'test_result' => 'success',
                    'member_name' => $member['fullName'] ?? 'Unknown',
                    'timestamp' => now()->toISOString(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Connection successful',
                    'member' => $member['fullName'] ?? 'Unknown',
                ];
            } else {
                $error = "HTTP {$response->status()}: " . $response->body();
                $this->integration->markSyncFailed($error);
                
                return [
                    'success' => false,
                    'message' => 'Connection failed',
                    'error' => $error,
                ];
            }
        } catch (Exception $e) {
            $this->integration->markSyncFailed($e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Connection error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getBoardInfo(): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/boards/{$this->integration->board_id}", [
                'key' => $this->integration->api_key,
                'token' => $this->integration->api_token,
                'fields' => 'id,name,url,desc',
                'lists' => 'open',
                'list_fields' => 'id,name,pos',
            ]);

            return $response->successful() ? $response->json() : null;
        } catch (Exception $e) {
            Log::error('Trello API Error: ' . $e->getMessage());
            return null;
        }
    }

    public function createCard(ContentItem $contentItem): ?array
    {
        try {
            $listId = $this->integration->list_id ?: $this->getDefaultListId();
            
            if (!$listId) {
                throw new Exception('No list ID specified and unable to find default list');
            }

            $response = Http::post("{$this->baseUrl}/cards", [
                'key' => $this->integration->api_key,
                'token' => $this->integration->api_token,
                'idList' => $listId,
                'name' => $contentItem->title,
                'desc' => $this->formatCardDescription($contentItem),
                'due' => $contentItem->scheduled_at?->toISOString(),
                'labels' => $this->getPlatformLabelId($contentItem->platform),
            ]);

            if ($response->successful()) {
                $card = $response->json();
                
                // Update content item with Trello card ID
                $contentItem->update(['trello_card_id' => $card['id']]);
                
                Log::info("Trello card created for content item {$contentItem->id}: {$card['id']}");
                
                return $card;
            } else {
                Log::error("Failed to create Trello card: " . $response->body());
                return null;
            }
        } catch (Exception $e) {
            Log::error('Trello card creation error: ' . $e->getMessage());
            return null;
        }
    }

    public function syncCommentToTrello(Comment $comment): bool
    {
        try {
            $variant = $comment->variant;
            
            if (!$variant->trello_card_id) {
                // Create card if it doesn't exist
                $card = $this->createCard($variant);
                if (!$card) {
                    return false;
                }
            }

            $response = Http::post("{$this->baseUrl}/cards/{$variant->trello_card_id}/actions/comments", [
                'key' => $this->integration->api_key,
                'token' => $this->integration->api_token,
                'text' => $this->formatCommentForTrello($comment),
            ]);

            if ($response->successful()) {
                Log::info("Comment {$comment->id} synced to Trello card {$variant->trello_card_id}");
                return true;
            } else {
                Log::error("Failed to sync comment to Trello: " . $response->body());
                return false;
            }
        } catch (Exception $e) {
            Log::error('Trello comment sync error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateCardStatus(Variant $variant): bool
    {
        try {
            if (!$variant->trello_card_id) {
                return false;
            }

            $listId = $this->getStatusListId($variant->status);
            
            if ($listId) {
                $response = Http::put("{$this->baseUrl}/cards/{$variant->trello_card_id}", [
                    'key' => $this->integration->api_key,
                    'token' => $this->integration->api_token,
                    'idList' => $listId,
                ]);

                return $response->successful();
            }

            return true; // No specific list for this status, but that's okay
        } catch (Exception $e) {
            Log::error('Trello card status update error: ' . $e->getMessage());
            return false;
        }
    }

    private function getDefaultListId(): ?string
    {
        $board = $this->getBoardInfo();
        
        if ($board && isset($board['lists']) && count($board['lists']) > 0) {
            return $board['lists'][0]['id'];
        }

        return null;
    }

    private function formatCardDescription(Variant $variant): string
    {
        $description = "**Content for {$variant->platform}**\n\n";
        $description .= "**Copy:**\n{$variant->copy}\n\n";
        
        if ($variant->media_url) {
            $description .= "**Media:** {$variant->media_url}\n\n";
        }
        
        $description .= "**Scheduled:** " . $variant->scheduled_at?->format('M j, Y @ g:i A') . "\n";
        $description .= "**Status:** {$variant->status}\n\n";
        $description .= "**Notes:**\n{$variant->notes}";

        return $description;
    }

    private function formatCommentForTrello(Comment $comment): string
    {
        $timestamp = $comment->created_at->format('M j, Y @ g:i A');
        return "**{$comment->author_name}** ({$timestamp}):\n\n{$comment->content}";
    }

    private function getPlatformLabelId(string $platform): ?string
    {
        // In a real implementation, you'd map platform types to Trello label colors
        // For now, we'll return null (no label)
        return null;
    }

    private function getStatusListId(string $status): ?string
    {
        // In a real implementation, you'd map status values to specific Trello list IDs
        // This would be configured per workspace/board
        return null;
    }

    public function setupWebhook(string $callbackUrl): ?array
    {
        try {
            $response = Http::post("{$this->baseUrl}/webhooks", [
                'key' => $this->integration->api_key,
                'token' => $this->integration->api_token,
                'description' => 'Joy Content Calendar Sync',
                'callbackURL' => $callbackUrl,
                'idModel' => $this->integration->board_id,
            ]);

            if ($response->successful()) {
                $webhook = $response->json();
                
                $this->integration->update([
                    'webhook_config' => [
                        'id' => $webhook['id'],
                        'callback_url' => $callbackUrl,
                        'active' => true,
                        'created_at' => now()->toISOString(),
                    ],
                ]);

                return $webhook;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Trello webhook setup error: ' . $e->getMessage());
            return null;
        }
    }

    public function syncWorkspaceToTrello(): array
    {
        $results = [
            'cards_created' => 0,
            'cards_updated' => 0,
            'comments_synced' => 0,
            'errors' => [],
        ];

        try {
            $variants = $this->integration->client->contentItems;

            foreach ($variants as $variant) {
                if (!$variant->trello_card_id) {
                    if ($this->createCard($variant)) {
                        $results['cards_created']++;
                    } else {
                        $results['errors'][] = "Failed to create card for variant {$variant->id}";
                    }
                } else {
                    if ($this->updateCardStatus($variant)) {
                        $results['cards_updated']++;
                    }
                }

                // Sync any unsynced comments
                foreach ($variant->comments as $comment) {
                    if ($this->syncCommentToTrello($comment)) {
                        $results['comments_synced']++;
                    } else {
                        $results['errors'][] = "Failed to sync comment {$comment->id}";
                    }
                }
            }

            $this->integration->markSyncCompleted([
                'status' => 'completed',
                'results' => $results,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (Exception $e) {
            $this->integration->markSyncFailed($e->getMessage());
            $results['errors'][] = $e->getMessage();
        }

        return $results;
    }
}