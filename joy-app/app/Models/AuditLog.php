<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class AuditLog extends Model
{
    protected $fillable = [
        'client_id',
        'user_id',
        'user_type',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'session_id',
        'request_data',
        'response_data',
        'severity',
        'tags',
        'expires_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'request_data' => 'array',
        'response_data' => 'array',
        'tags' => 'array',
        'expires_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function auditable()
    {
        return $this->morphTo();
    }

    // Scope filters
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForUser(Builder $query, int $userId, string $userType = null): Builder
    {
        $query->where('user_id', $userId);
        
        if ($userType) {
            $query->where('user_type', $userType);
        }
        
        return $query;
    }

    public function scopeForAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    public function scopeForModel(Builder $query, string $modelType, int $modelId = null): Builder
    {
        $query->where('auditable_type', $modelType);
        
        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }
        
        return $query;
    }

    public function scopeBySeverity(Builder $query, string $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('expires_at', '<=', now());
    }

    // Helper methods
    public function getUserDisplayName(): string
    {
        if ($this->user_type === 'magic_link') {
            return "Client Access (ID: {$this->user_id})";
        }
        
        return "User ID: {$this->user_id}";
    }

    public function getActionDisplayName(): string
    {
        return match($this->action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'viewed' => 'Viewed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'commented' => 'Added Comment',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'magic_link_accessed' => 'Accessed via Magic Link',
            'trello_sync' => 'Synced to Trello',
            'export' => 'Exported Data',
            default => ucfirst($this->action)
        };
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'critical' => 'text-red-600 bg-red-100',
            'error' => 'text-red-600 bg-red-50',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'info' => 'text-blue-600 bg-blue-50',
            'debug' => 'text-gray-600 bg-gray-100',
            default => 'text-gray-600 bg-gray-50'
        };
    }

    public function hasAuditChanges(): bool
    {
        return !empty($this->old_values) || !empty($this->new_values);
    }

    public function getChangedFields(): array
    {
        if (!$this->hasAuditChanges()) {
            return [];
        }

        $oldValues = $this->old_values ?? [];
        $newValues = $this->new_values ?? [];
        
        return array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
    }

    public static function log(array $data): self
    {
        // Set default expires_at to 90 days from now if not specified
        if (!isset($data['expires_at'])) {
            $data['expires_at'] = now()->addDays(90);
        }

        return self::create($data);
    }

    public static function cleanupExpired(): int
    {
        return self::expired()->delete();
    }
}
