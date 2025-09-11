<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MagicLink extends Model
{
    protected $fillable = [
        'client_id',
        'token',
        'email',
        'name',
        'permissions',
        'expires_at',
        'last_accessed_at',
        'access_count',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'expires_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function isValid(): bool
    {
        return $this->is_active 
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAccessed(): void
    {
        $this->increment('access_count');
        $this->update(['last_accessed_at' => now()]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public static function createForClient(
        Client $client, 
        string $email, 
        string $name, 
        array $permissions = [], 
        int $expiresInHours = 168
    ): self {
        return self::create([
            'client_id' => $client->id,
            'token' => self::generateToken(),
            'email' => $email,
            'name' => $name,
            'permissions' => $permissions,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }
}
