<?php

namespace App\Services;

use App\Models\AuditLog;

class AuditLogFormatter
{
    /**
     * Get display name for a user based on audit log data
     */
    public function getUserDisplayName(AuditLog $auditLog): string
    {
        if ($auditLog->user_type === 'magic_link') {
            return "Client Access (ID: {$auditLog->user_id})";
        }
        
        if ($auditLog->user_type === 'anonymous') {
            return 'Anonymous User';
        }
        
        // Try to get actual user name if available
        if ($auditLog->user_id && class_exists('App\Models\User')) {
            $user = \App\Models\User::find($auditLog->user_id);
            if ($user) {
                return $user->name ?? "User ID: {$auditLog->user_id}";
            }
        }
        
        return "User ID: {$auditLog->user_id}";
    }

    /**
     * Get display name for an action
     */
    public function getActionDisplayName(AuditLog $auditLog): string
    {
        return match($auditLog->action) {
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
            default => ucfirst($auditLog->action)
        };
    }

    /**
     * Get CSS classes for severity styling
     */
    public function getSeverityColor(AuditLog $auditLog): string
    {
        return match($auditLog->severity) {
            'critical' => 'text-red-600 bg-red-100',
            'error' => 'text-red-600 bg-red-50',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'info' => 'text-blue-600 bg-blue-50',
            'debug' => 'text-gray-600 bg-gray-100',
            default => 'text-gray-600 bg-gray-50'
        };
    }

    /**
     * Get human-readable model name
     */
    public function getModelDisplayName(AuditLog $auditLog): string
    {
        if (!$auditLog->auditable_type) {
            return 'System';
        }

        // Extract class name from full namespace
        $modelName = class_basename($auditLog->auditable_type);
        
        // Convert camelCase to Title Case with spaces
        return preg_replace('/(?<!^)([A-Z])/', ' $1', $modelName);
    }

    /**
     * Format the audit log entry as a human-readable summary
     */
    public function getSummary(AuditLog $auditLog): string
    {
        $user = $this->getUserDisplayName($auditLog);
        $action = $this->getActionDisplayName($auditLog);
        $model = $this->getModelDisplayName($auditLog);
        
        if ($auditLog->auditable_id) {
            return "{$user} {$action} {$model} (ID: {$auditLog->auditable_id})";
        }
        
        if ($auditLog->auditable_type) {
            return "{$user} {$action} {$model}";
        }
        
        return "{$user} {$action}";
    }

    /**
     * Get severity badge HTML
     */
    public function getSeverityBadge(AuditLog $auditLog): string
    {
        $classes = $this->getSeverityColor($auditLog);
        $severity = ucfirst($auditLog->severity);
        
        return "<span class=\"inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$classes}\">{$severity}</span>";
    }

    /**
     * Format timestamp in a user-friendly way
     */
    public function getFormattedTimestamp(AuditLog $auditLog): string
    {
        return $auditLog->created_at->format('M j, Y \a\t g:i A');
    }

    /**
     * Get relative timestamp (e.g., "2 hours ago")
     */
    public function getRelativeTimestamp(AuditLog $auditLog): string
    {
        return $auditLog->created_at->diffForHumans();
    }

    /**
     * Format changed fields for display
     */
    public function getChangedFieldsSummary(AuditLog $auditLog): array
    {
        $analyzer = app(AuditLogAnalyzer::class);
        $changedFields = $analyzer->getChangedFields($auditLog);
        
        $summary = [];
        foreach ($changedFields as $field) {
            $oldValue = $auditLog->old_values[$field] ?? null;
            $newValue = $auditLog->new_values[$field] ?? null;
            
            $summary[] = [
                'field' => $this->formatFieldName($field),
                'old_value' => $this->formatValue($oldValue),
                'new_value' => $this->formatValue($newValue),
            ];
        }
        
        return $summary;
    }

    /**
     * Format field name for display
     */
    private function formatFieldName(string $field): string
    {
        // Convert snake_case to Title Case
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format a value for display
     */
    private function formatValue($value): string
    {
        if ($value === null) {
            return '<em>null</em>';
        }
        
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_array($value)) {
            return '[' . implode(', ', array_slice($value, 0, 3)) . (count($value) > 3 ? '...' : '') . ']';
        }
        
        if (is_string($value) && strlen($value) > 50) {
            return substr($value, 0, 50) . '...';
        }
        
        return (string) $value;
    }
}