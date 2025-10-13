<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogCreator
{
    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_ERROR = 'error';
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_INFO = 'info';
    public const SEVERITY_DEBUG = 'debug';

    public const ACTION_CREATED = 'created';
    public const ACTION_UPDATED = 'updated';
    public const ACTION_DELETED = 'deleted';
    public const ACTION_VIEWED = 'viewed';
    public const ACTION_APPROVED = 'approved';
    public const ACTION_REJECTED = 'rejected';
    public const ACTION_COMMENTED = 'commented';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_MAGIC_LINK_ACCESSED = 'magic_link_accessed';
    public const ACTION_TRELLO_SYNC = 'trello_sync';
    public const ACTION_EXPORT = 'export';

    /**
     * Create a new audit log entry
     */
    public function log(array $data): AuditLog
    {
        $data = $this->enrichLogData($data);
        return AuditLog::create($data);
    }

    /**
     * Log a model creation event
     */
    public function logCreated($model, ?int $clientId = null, ?int $userId = null): AuditLog
    {
        return $this->log([
            'client_id' => $clientId,
            'user_id' => $userId ?? auth()->id(),
            'event' => self::ACTION_CREATED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'new_values' => $this->getModelData($model),
        ]);
    }

    /**
     * Log a model update event
     */
    public function logUpdated($model, array $oldValues, ?int $clientId = null, ?int $userId = null): AuditLog
    {
        return $this->log([
            'client_id' => $clientId,
            'user_id' => $userId ?? auth()->id(),
            'event' => self::ACTION_UPDATED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $this->getModelData($model),
        ]);
    }

    /**
     * Log a model deletion event
     */
    public function logDeleted($model, ?int $clientId = null, ?int $userId = null): AuditLog
    {
        return $this->log([
            'client_id' => $clientId,
            'user_id' => $userId ?? auth()->id(),
            'event' => self::ACTION_DELETED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $this->getModelData($model),
        ]);
    }

    /**
     * Log a user action
     */
    public function logUserAction(string $event, ?int $clientId = null, array $metadata = []): AuditLog
    {
        return $this->log([
            'client_id' => $clientId,
            'user_id' => auth()->id(),
            'event' => $event,
            'new_values' => $metadata,
        ]);
    }

    /**
     * Log a magic link access
     */
    public function logMagicLinkAccess(int $magicLinkId, int $workspaceId, string $event): AuditLog
    {
        return $this->log([
            'client_id' => $workspaceId,
            'user_id' => $magicLinkId,
            'event' => $event,
        ]);
    }

    /**
     * Enrich log data with default values and request context
     */
    private function enrichLogData(array $data): array
    {
        // Add request context if not provided
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = Request::ip();
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = Request::userAgent();
        }

        return $data;
    }

    /**
     * Get the current user type
     */
    private function getUserType(): string
    {
        if (!auth()->check()) {
            return 'anonymous';
        }

        $user = auth()->user();
        
        if ($user->hasRole('admin')) {
            return 'admin';
        }
        
        if ($user->hasRole('Account Manager') || $user->hasRole('agency')) {
            return 'agency';
        }
        
        return 'client';
    }

    /**
     * Extract relevant data from a model for logging
     */
    private function getModelData($model): array
    {
        if (method_exists($model, 'toAuditArray')) {
            return $model->toAuditArray();
        }

        // Get fillable attributes or all attributes if fillable is not defined
        $attributes = $model->getFillable();
        if (empty($attributes)) {
            $attributes = array_keys($model->getAttributes());
        }

        $data = [];
        foreach ($attributes as $attribute) {
            if (isset($model->$attribute)) {
                $data[$attribute] = $model->$attribute;
            }
        }

        return $data;
    }
}