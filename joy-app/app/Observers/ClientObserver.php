<?php

namespace App\Observers;

use App\Models\Client;
use App\Models\AuditLog;

class ClientObserver
{
    /**
     * Handle the Client "created" event.
     */
    public function created(Client $client): void
    {
        AuditLog::log([
            'event' => 'Client Created',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'new_values' => [
                'name' => $client->name,
                'description' => $client->description,
                'team_id' => $client->team_id,
                'slack_channel_id' => $client->slack_channel_id,
                'slack_channel_name' => $client->slack_channel_name,
            ],
        ]);
    }

    /**
     * Handle the Client "updated" event.
     */
    public function updated(Client $client): void
    {
        // Only log if actual changes occurred
        if ($client->wasChanged()) {
            AuditLog::log([
                'event' => 'Client Updated',
                'auditable_type' => Client::class,
                'auditable_id' => $client->id,
                'user_id' => auth()->id(),
                'client_id' => $client->id,
                'old_values' => $client->getOriginal(),
                'new_values' => $client->getChanges(),
            ]);
        }
    }

    /**
     * Handle the Client "deleted" event.
     */
    public function deleted(Client $client): void
    {
        AuditLog::log([
            'event' => 'Client Deleted',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'old_values' => [
                'name' => $client->name,
                'description' => $client->description,
                'team_id' => $client->team_id,
            ],
        ]);
    }

    /**
     * Handle the Client "restored" event.
     */
    public function restored(Client $client): void
    {
        AuditLog::log([
            'event' => 'Client Restored',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'user_id' => auth()->id(),
            'client_id' => $client->id,
            'new_values' => [
                'name' => $client->name,
                'description' => $client->description,
                'team_id' => $client->team_id,
            ],
        ]);
    }
}
