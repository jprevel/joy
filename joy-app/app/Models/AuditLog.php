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

    public function scopeForUser(Builder $query, int $userId, ?string $userType = null): Builder
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

    public function scopeForModel(Builder $query, string $modelType, ?int $modelId = null): Builder
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

    // Helper methods - delegated to services
    public function getUserDisplayName(): string
    {
        $formatter = app(\App\Services\AuditLogFormatter::class);
        return $formatter->getUserDisplayName($this);
    }

    public function getActionDisplayName(): string
    {
        $formatter = app(\App\Services\AuditLogFormatter::class);
        return $formatter->getActionDisplayName($this);
    }

    public function getSeverityColor(): string
    {
        $formatter = app(\App\Services\AuditLogFormatter::class);
        return $formatter->getSeverityColor($this);
    }

    public function hasAuditChanges(): bool
    {
        $analyzer = app(\App\Services\AuditLogAnalyzer::class);
        return $analyzer->hasAuditChanges($this);
    }

    public function getChangedFields(): array
    {
        $analyzer = app(\App\Services\AuditLogAnalyzer::class);
        return $analyzer->getChangedFields($this);
    }

    public static function log(array $data): self
    {
        $creator = app(\App\Services\AuditLogCreator::class);
        return $creator->log($data);
    }

    public static function cleanupExpired(): int
    {
        $cleanup = app(\App\Services\AuditLogCleanup::class);
        return $cleanup->cleanupExpired();
    }
}
