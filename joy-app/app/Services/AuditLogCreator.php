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
            'user_type' => $this->getUserType(),
            'action' => self::ACTION_CREATED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'new_values' => $this->getModelData($model),
            'severity' => self::SEVERITY_INFO,
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
            'user_type' => $this->getUserType(),
            'action' => self::ACTION_UPDATED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $this->getModelData($model),
            'severity' => self::SEVERITY_INFO,
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
            'user_type' => $this->getUserType(),
            'action' => self::ACTION_DELETED,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->id,
            'old_values' => $this->getModelData($model),
            'severity' => self::SEVERITY_WARNING,
        ]);
    }

    /**
     * Log a user action
     */
    public function logUserAction(string $action, ?int $clientId = null, array $metadata = []): AuditLog
    {
        return $this->log([
            'client_id' => $clientId,
            'user_id' => auth()->id(),
            'user_type' => $this->getUserType(),
            'action' => $action,
            'request_data' => $metadata,
            'severity' => self::SEVERITY_INFO,
        ]);
    }

    /**
     * Log a magic link access
     */
    public function logMagicLinkAccess(int $magicLinkId, int $workspaceId, string $action): AuditLog
    {
        return $this->log([
            'client_id' => $workspaceId,
            'user_id' => $magicLinkId,
            'user_type' => 'magic_link',
            'action' => $action,
            'severity' => self::SEVERITY_INFO,
            'tags' => ['magic_link_access'],
        ]);
    }

    /**
     * Enrich log data with default values and request context
     */
    private function enrichLogData(array $data): array
    {
        // Set default expires_at to 90 days from now if not specified
        if (!isset($data['expires_at'])) {
            $data['expires_at'] = now()->addDays(90);
        }

        // Add request context if not provided
        if (!isset($data['ip_address'])) {
            $data['ip_address'] = Request::ip();
        }

        if (!isset($data['user_agent'])) {
            $data['user_agent'] = Request::userAgent();
        }

        if (!isset($data['session_id'])) {
            $data['session_id'] = session()->getId();
        }

        // Set default severity if not provided
        if (!isset($data['severity'])) {
            $data['severity'] = self::SEVERITY_INFO;
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