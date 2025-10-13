<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlackNotification extends Model
{
    protected $fillable = [
        'workspace_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'channel_id',
        'channel_name',
        'status',
        'payload',
        'response',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the workspace this notification belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(SlackWorkspace::class, 'workspace_id');
    }

    /**
     * Get the owning notifiable model (Comment, ContentItem, ClientStatusfactionUpdate).
     */
    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get failed notifications.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope to get sent notifications.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to filter by channel.
     */
    public function scopeForChannel($query, string $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    /**
     * Mark notification as sent.
     */
    public function markAsSent(array $response): void
    {
        $this->update([
            'status' => 'sent',
            'response' => $response,
            'sent_at' => now(),
        ]);
    }

    /**
     * Mark notification as failed.
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
