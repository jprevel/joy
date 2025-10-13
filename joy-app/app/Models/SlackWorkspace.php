<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlackWorkspace extends Model
{
    use HasFactory;
    protected $fillable = [
        'team_id',
        'team_name',
        'bot_token',
        'access_token',
        'scopes',
        'bot_user_id',
        'is_active',
        'last_sync_at',
        'last_error',
        'metadata',
    ];

    protected $casts = [
        'bot_token' => 'encrypted',
        'access_token' => 'encrypted',
        'scopes' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Get all notifications for this workspace.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(SlackNotification::class, 'workspace_id');
    }

    /**
     * Check if workspace is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the default active workspace.
     */
    public static function getDefault(): ?self
    {
        return self::where('is_active', true)->first();
    }
}
