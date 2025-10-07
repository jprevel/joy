<?php

namespace App\Services;

use App\Models\Client;
use App\Models\User;

class ClientAccessResolver
{
    public function __construct(
        private RoleDetectionService $roleDetection
    ) {}

    /**
     * Resolve the client from client_id parameter or authenticated user
     *
     * @param  int|null  $clientId
     * @param  User  $user
     * @return Client
     * @throws \InvalidArgumentException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveClient(?int $clientId, User $user): Client
    {
        if ($clientId) {
            $client = Client::findOrFail($clientId);
            $this->validateAccess($user, $client);
            return $client;
        }

        if ($this->roleDetection->isClient($user)) {
            return $user->client;
        }

        throw new \InvalidArgumentException('client_id parameter required for admin/agency users');
    }

    /**
     * Validate that the user has access to the given client
     *
     * @param  User  $user
     * @param  Client  $client
     * @return void
     * @throws \RuntimeException
     */
    public function validateAccess(User $user, Client $client): void
    {
        if (!$this->roleDetection->canAccessClient($user, $client)) {
            throw new \RuntimeException('You do not have access to this client');
        }
    }
}
