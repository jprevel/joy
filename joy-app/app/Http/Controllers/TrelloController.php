<?php

namespace App\Http\Controllers;
use App\Http\Traits\ApiResponse;

use App\Models\Client;
use App\Models\ContentItem;
use App\Services\TrelloService;
use App\Services\TrelloStatsService;
use App\Services\RoleDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrelloController extends Controller
{
    use ApiResponse;

    public function __construct(
        private TrelloService $trelloService,
        private TrelloStatsService $trelloStatsService,
        private RoleDetectionService $roleDetectionService
    ) {}

    /**
     * Test Trello connection for a client.
     */
    public function testConnection(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'client_id' => 'required|exists:clients,id'
            ]);

            $client = Client::findOrFail($request->input('client_id'));
            $result = $this->trelloService->testConnection($client);

            return $this->success($result);

        } catch (\Exception $e) {
            return $this->serverError('Connection test failed', $e);
        }
    }

    /**
     * Get Trello integration status for a client.
     */
    public function status(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'client_id' => 'required|exists:clients,id'
            ]);

            $client = Client::findOrFail($request->input('client_id'));
            $status = $this->trelloService->getIntegrationStatus($client);

            return $this->success([
                'data' => $status,
                'client_id' => $client->id,
                'client_name' => $client->name
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Failed to get integration status', $e);
        }
    }

    /**
     * Create Trello card for a content item.
     */
    public function createCard(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'content_item_id' => 'required|exists:content_items,id'
            ]);

            $contentItem = ContentItem::findOrFail($request->input('content_item_id'));

            if ($this->userCannotAccessContent($user, $contentItem)) {
                return $this->forbidden();
            }

            $trelloCard = $this->trelloService->createCardForContent($contentItem);

            if ($this->cardWasCreatedSuccessfully($trelloCard)) {
                return $this->created([
                    'trello_card_id' => $trelloCard->trello_card_id,
                    'content_item_id' => $contentItem->id,
                    'sync_status' => $trelloCard->sync_status
                ], 'Trello card created successfully');
            } else {
                return $this->error('Card creation failed or Trello integration not configured', 400);
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to create Trello card', $e);
        }
    }

    /**
     * Sync all content for a client to Trello.
     */
    public function syncClient(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'client_id' => 'required|exists:clients,id'
            ]);

            $client = Client::findOrFail($request->input('client_id'));
            $results = $this->trelloService->syncClientToTrello($client);

            return $this->success([
                'data' => $results,
                'client_id' => $client->id,
                'client_name' => $client->name
            ]);

        } catch (\Exception $e) {
            return $this->serverError('Sync failed', $e);
        }
    }

    /**
     * Process pending Trello syncs.
     */
    public function processPendingSyncs(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $results = $this->trelloService->processPendingSyncs();

            return $this->success(
                $results,
                'Pending syncs processed'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to process pending syncs', $e);
        }
    }

    /**
     * Update Trello card status for content item.
     */
    public function updateCardStatus(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'content_item_id' => 'required|exists:content_items,id'
            ]);

            $contentItem = ContentItem::findOrFail($request->input('content_item_id'));

            // Check if user can access this content item
            if (!$this->roleDetectionService->canAccessContent($user, $contentItem)) {
                return $this->forbidden();
            }

            $success = $this->trelloService->updateCardStatus($contentItem);

            if ($success) {
                return $this->success(null, 'Trello card status updated successfully');
            } else {
                return $this->error('Update failed or no Trello card found', 400);
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to update card status', $e);
        }
    }

    /**
     * Get Trello board information.
     */
    public function boardInfo(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $boardInfo = $this->trelloService->getBoardInfo();

            if ($boardInfo) {
                return $this->success($boardInfo);
            } else {
                return $this->notFound('Board not found or integration not configured');
            }

        } catch (\Exception $e) {
            return $this->serverError('Failed to get board information', $e);
        }
    }

    /**
     * Get Trello sync statistics.
     */
    public function syncStats(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAgency($user)) {
            return $this->forbidden();
        }

        try {
            $clientId = $request->input('client_id');

            if ($clientId) {
                $client = Client::findOrFail($clientId);
                $stats = $this->trelloStatsService->getClientStats($client);
            } else {
                // System-wide stats for admins
                if (!$this->roleDetectionService->isAdmin($user)) {
                    return $this->forbidden();
                }

                $stats = $this->trelloStatsService->getSystemStats();
            }

            return $this->success($stats);

        } catch (\Exception $e) {
            return $this->serverError('Failed to get sync statistics', $e);
        }
    }

    /**
     * Retry failed Trello syncs.
     */
    public function retryFailedSyncs(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $clientId = $request->input('client_id');
            $results = $this->trelloStatsService->retryFailedSyncs($clientId, $this->trelloService);

            return $this->success(
                $results,
                'Retry operation completed'
            );

        } catch (\Exception $e) {
            return $this->serverError('Failed to retry syncs', $e);
        }
    }

    /**
     * Bulk sync operations.
     */
    public function bulkSync(Request $request): JsonResponse
    {
        // User resolved by middleware
        $user = $request->get('authenticated_user');

        if (!$this->roleDetectionService->isAdmin($user)) {
            return $this->forbidden();
        }

        try {
            $request->validate([
                'action' => 'required|string|in:sync_all,retry_failed,cleanup_old',
                'client_ids' => 'sometimes|array',
                'client_ids.*' => 'exists:clients,id'
            ]);

            $action = $request->input('action');
            $clientIds = $request->input('client_ids');

            $results = ['processed' => 0, 'success' => 0, 'failed' => 0, 'errors' => []];

            $clients = $clientIds ? Client::whereIn('id', $clientIds)->get() : Client::all();

            foreach ($clients as $client) {
                $results['processed']++;

                try {
                    switch ($action) {
                        case 'sync_all':
                            $this->trelloService->syncClientToTrello($client);
                            break;
                        case 'retry_failed':
                            // Handle retry for this client specifically
                            break;
                        case 'cleanup_old':
                            // Handle cleanup for this client
                            break;
                    }

                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = "Client {$client->id}: {$e->getMessage()}";
                }
            }

            return $this->success(
                $results,
                "Bulk {$action} completed"
            );

        } catch (\Exception $e) {
            return $this->serverError('Bulk operation failed', $e);
        }
    }

    /**
     * Check if user cannot access the content item.
     */
    private function userCannotAccessContent($user, ContentItem $contentItem): bool
    {
        return !$this->roleDetectionService->canAccessContent($user, $contentItem);
    }

    /**
     * Check if Trello card was created successfully.
     */
    private function cardWasCreatedSuccessfully($trelloCard): bool
    {
        return $trelloCard !== null;
    }
}