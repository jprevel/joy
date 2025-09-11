<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class TrelloIntegration extends Model
{
    protected $fillable = [
        'client_id',
        'api_key',
        'api_token',
        'board_id',
        'list_id',
        'webhook_config',
        'is_active',
        'last_sync_at',
        'sync_status',
    ];

    protected $casts = [
        'webhook_config' => 'array',
        'sync_status' => 'array',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_token',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }

    protected function apiToken(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => decrypt($value),
            set: fn (string $value) => encrypt($value),
        );
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_key) 
            && !empty($this->api_token) 
            && !empty($this->board_id);
    }

    public function getAuthHeaders(): array
    {
        return [
            'Authorization' => "OAuth oauth_consumer_key=\"{$this->api_key}\", oauth_token=\"{$this->api_token}\"",
            'Content-Type' => 'application/json',
        ];
    }

    public function getBoardUrl(): ?string
    {
        return $this->board_id ? "https://trello.com/b/{$this->board_id}" : null;
    }

    public function markSyncCompleted(array $status = []): void
    {
        $this->update([
            'last_sync_at' => now(),
            'sync_status' => array_merge($this->sync_status ?? [], $status),
        ]);
    }

    public function markSyncFailed(string $error): void
    {
        $this->update([
            'sync_status' => [
                'status' => 'failed',
                'error' => $error,
                'timestamp' => now()->toISOString(),
            ],
        ]);
    }
}
