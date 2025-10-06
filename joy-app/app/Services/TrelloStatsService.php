<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Comment;
use App\Models\TrelloCard;

class TrelloStatsService
{
    /**
     * Get client-specific Trello statistics.
     */
    public function getClientStats(Client $client): array
    {
        $totalContent = $client->contentItems()->count();
        $syncedContent = $client->contentItems()->whereHas('trelloCard')->count();

        $totalComments = Comment::whereHas('contentItem', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->count();

        $syncedComments = Comment::whereHas('contentItem', function ($q) use ($client) {
            $q->where('client_id', $client->id);
        })->whereHas('trelloCard')->count();

        return [
            'client_id' => $client->id,
            'client_name' => $client->name,
            'integration_configured' => $client->hasTrelloIntegration(),
            'total_content_items' => $totalContent,
            'synced_content_items' => $syncedContent,
            'sync_rate_content' => $totalContent > 0
                ? round(($syncedContent / $totalContent) * 100, 2)
                : 0,
            'total_comments' => $totalComments,
            'synced_comments' => $syncedComments,
            'sync_rate_comments' => $totalComments > 0
                ? round(($syncedComments / $totalComments) * 100, 2)
                : 0,
        ];
    }

    /**
     * Get system-wide Trello statistics.
     */
    public function getSystemStats(): array
    {
        $totalClients = Client::count();
        $integratedClients = Client::whereNotNull('trello_board_id')
            ->whereNotNull('trello_list_id')
            ->count();

        $totalCards = TrelloCard::count();
        $pendingCards = TrelloCard::where('sync_status', 'pending')->count();
        $failedCards = TrelloCard::where('sync_status', 'failed')->count();

        return [
            'total_clients' => $totalClients,
            'integrated_clients' => $integratedClients,
            'integration_rate' => $totalClients > 0
                ? round(($integratedClients / $totalClients) * 100, 2)
                : 0,
            'total_cards' => $totalCards,
            'pending_syncs' => $pendingCards,
            'failed_syncs' => $failedCards,
            'sync_health' => $totalCards > 0
                ? round((($totalCards - $failedCards) / $totalCards) * 100, 2)
                : 100,
        ];
    }

    /**
     * Retry failed syncs for a client or all clients.
     */
    public function retryFailedSyncs(?int $clientId, TrelloService $trelloService): array
    {
        $query = TrelloCard::where('sync_status', 'failed');

        if ($clientId) {
            $query->whereHas('contentItem', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            })->orWhereHas('comment.contentItem', function ($q) use ($clientId) {
                $q->where('client_id', $clientId);
            });
        }

        $failedCards = $query->get();
        $results = ['processed' => 0, 'success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($failedCards as $card) {
            $results['processed']++;

            try {
                $card->update(['sync_status' => 'pending']);

                if ($card->content_item_id) {
                    $trelloService->createCardForContent($card->contentItem);
                } elseif ($card->comment_id) {
                    $trelloService->syncCommentToTrello($card->comment);
                }

                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Card {$card->id}: {$e->getMessage()}";
            }
        }

        return $results;
    }
}
