<?php

namespace App\Services;

use App\Contracts\ClientManagementContract;
use App\Models\Client;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ClientManagementService implements ClientManagementContract
{
    public function __construct(
        protected SlackService $slackService
    ) {}

    /**
     * Get paginated list of all clients including soft-deleted ones
     */
    public function listClients(int $perPage = 15, bool $includeTrashed = true): LengthAwarePaginator
    {
        $query = $includeTrashed ? Client::withTrashed() : Client::query();
        return $query->with('team')->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Create a new client with team and Slack channel assignment
     */
    public function createClient(array $data): Client
    {
        $validated = $this->validateClientData($data);

        $client = Client::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'team_id' => $validated['team_id'],
            'slack_channel_id' => $validated['slack_channel_id'] ?? null,
            'slack_channel_name' => $validated['slack_channel_name'] ?? null,
        ]);

        return $client->load('team');
    }

    /**
     * Update existing client details including Slack channel
     */
    public function updateClient(int $clientId, array $data): Client
    {
        $validated = $this->validateClientData($data, $clientId);
        $client = $this->findClient($clientId);

        $client->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $client->description,
            'team_id' => $validated['team_id'],
            'slack_channel_id' => $validated['slack_channel_id'] ?? $client->slack_channel_id,
            'slack_channel_name' => $validated['slack_channel_name'] ?? $client->slack_channel_name,
        ]);

        return $client->fresh('team');
    }

    /**
     * Soft delete a client (preserves relationships and magic links)
     */
    public function deleteClient(int $clientId): bool
    {
        $client = $this->findClient($clientId);
        return $client->delete();
    }

    /**
     * Restore a soft-deleted client
     */
    public function restoreClient(int $clientId): Client
    {
        $client = $this->findClient($clientId, true);

        if (!$client->trashed()) {
            throw new \RuntimeException('Client is not deleted');
        }

        $client->restore();
        return $client->fresh('team');
    }

    /**
     * Get client by ID including soft-deleted clients
     */
    public function findClient(int $clientId, bool $includeTrashed = true): Client
    {
        $query = $includeTrashed ? Client::withTrashed() : Client::query();
        return $query->with('team')->findOrFail($clientId);
    }

    /**
     * Get available Slack channels from connected workspace
     */
    public function getAvailableSlackChannels(): array
    {
        $result = $this->slackService->getChannels(includeArchived: false, includePrivate: true);

        if (!($result['success'] ?? false)) {
            return [];
        }

        $channels = $result['channels'] ?? [];

        return array_map(function ($channel) {
            return [
                'id' => $channel['id'],
                'name' => $channel['name'],
                'is_private' => $channel['is_private'] ?? false,
            ];
        }, $channels);
    }

    /**
     * Get available teams for client assignment
     */
    public function getAvailableTeams(): Collection
    {
        return Team::orderBy('name')->get();
    }

    /**
     * Validate client data before creation or update
     */
    public function validateClientData(array $data, ?int $clientId = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'team_id' => ['required', 'integer', 'exists:teams,id'],
            'slack_channel_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'slack_channel_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Check if client has active content items
     */
    public function hasActiveContent(int $clientId): bool
    {
        $client = $this->findClient($clientId);
        return $client->contentItems()->exists();
    }

    /**
     * Check if client has active magic links
     */
    public function hasActiveMagicLinks(int $clientId): bool
    {
        $client = $this->findClient($clientId);
        return $client->magicLinks()->where('expires_at', '>', now())->exists();
    }
}
