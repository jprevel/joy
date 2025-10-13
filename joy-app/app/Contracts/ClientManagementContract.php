<?php

namespace App\Contracts;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contract for Client Management operations in admin section
 *
 * Defines CRUD operations for Client entities with soft delete support,
 * Slack channel mapping, and team assignment.
 *
 * @package App\Contracts
 */
interface ClientManagementContract
{
    /**
     * Get paginated list of all clients including soft-deleted ones
     *
     * @param int $perPage Number of clients per page
     * @param bool $includeTrashed Whether to include soft-deleted clients
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function listClients(int $perPage = 15, bool $includeTrashed = true);

    /**
     * Create a new client with team and Slack channel assignment
     *
     * @param array $data Client data [name, description, team_id, slack_channel_id, slack_channel_name]
     * @return Client Created client instance
     * @throws \Illuminate\Validation\ValidationException
     */
    public function createClient(array $data): Client;

    /**
     * Update existing client details including Slack channel
     *
     * @param int $clientId Client ID to update
     * @param array $data Updated client data
     * @return Client Updated client instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function updateClient(int $clientId, array $data): Client;

    /**
     * Soft delete a client (preserves relationships and magic links)
     *
     * @param int $clientId Client ID to delete
     * @return bool True if deleted successfully
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function deleteClient(int $clientId): bool;

    /**
     * Restore a soft-deleted client
     *
     * @param int $clientId Client ID to restore
     * @return Client Restored client instance
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function restoreClient(int $clientId): Client;

    /**
     * Get client by ID including soft-deleted clients
     *
     * @param int $clientId Client ID
     * @param bool $includeTrashed Whether to include soft-deleted clients
     * @return Client
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findClient(int $clientId, bool $includeTrashed = true): Client;

    /**
     * Get available Slack channels from connected workspace
     *
     * @return array Array of channels [['id' => 'C123', 'name' => 'general'], ...]
     */
    public function getAvailableSlackChannels(): array;

    /**
     * Get available teams for client assignment
     *
     * @return Collection Collection of Team models
     */
    public function getAvailableTeams(): Collection;

    /**
     * Validate client data before creation or update
     *
     * @param array $data Client data to validate
     * @param int|null $clientId Client ID for updates (null for creation)
     * @return array Validated data
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validateClientData(array $data, ?int $clientId = null): array;

    /**
     * Check if client has active content items
     *
     * @param int $clientId Client ID
     * @return bool True if client has content items
     */
    public function hasActiveContent(int $clientId): bool;

    /**
     * Check if client has active magic links
     *
     * @param int $clientId Client ID
     * @return bool True if client has unexpired magic links
     */
    public function hasActiveMagicLinks(int $clientId): bool;
}
