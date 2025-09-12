<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AuditLogAnalyzer
{
    /**
     * Check if audit log has changes recorded
     */
    public function hasAuditChanges(AuditLog $auditLog): bool
    {
        return !empty($auditLog->old_values) || !empty($auditLog->new_values);
    }

    /**
     * Get list of changed fields
     */
    public function getChangedFields(AuditLog $auditLog): array
    {
        if (!$this->hasAuditChanges($auditLog)) {
            return [];
        }

        $oldValues = $auditLog->old_values ?? [];
        $newValues = $auditLog->new_values ?? [];
        
        return array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
    }

    /**
     * Get detailed change analysis for specific fields
     */
    public function getFieldChanges(AuditLog $auditLog): array
    {
        $changes = [];
        $changedFields = $this->getChangedFields($auditLog);

        foreach ($changedFields as $field) {
            $oldValue = $auditLog->old_values[$field] ?? null;
            $newValue = $auditLog->new_values[$field] ?? null;

            $changes[$field] = [
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'changed' => $oldValue !== $newValue,
                'added' => $oldValue === null && $newValue !== null,
                'removed' => $oldValue !== null && $newValue === null,
                'modified' => $oldValue !== null && $newValue !== null && $oldValue !== $newValue,
            ];
        }

        return $changes;
    }

    /**
     * Get audit statistics for a given period
     */
    public function getStatistics(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_logs' => AuditLog::where('created_at', '>=', $startDate)->count(),
            'by_action' => $this->getActionStatistics($startDate),
            'by_severity' => $this->getSeverityStatistics($startDate),
            'by_user_type' => $this->getUserTypeStatistics($startDate),
            'by_day' => $this->getDailyStatistics($startDate),
        ];
    }

    /**
     * Get most active users in audit logs
     */
    public function getMostActiveUsers(int $days = 30, int $limit = 10): array
    {
        return AuditLog::where('created_at', '>=', now()->subDays($days))
            ->select('user_id', 'user_type', DB::raw('COUNT(*) as activity_count'))
            ->groupBy('user_id', 'user_type')
            ->orderBy('activity_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'user_id' => $log->user_id,
                    'user_type' => $log->user_type,
                    'activity_count' => $log->activity_count,
                ];
            })
            ->toArray();
    }

    /**
     * Get audit trail for a specific model
     */
    public function getModelAuditTrail(string $modelType, int $modelId): Collection
    {
        return AuditLog::forModel($modelType, $modelId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find suspicious activity patterns
     */
    public function findSuspiciousActivity(int $days = 7): array
    {
        $startDate = now()->subDays($days);
        $suspicious = [];

        // Multiple failed login attempts
        $failedLogins = AuditLog::where('created_at', '>=', $startDate)
            ->where('action', 'login_failed')
            ->select('ip_address', DB::raw('COUNT(*) as attempt_count'))
            ->groupBy('ip_address')
            ->having('attempt_count', '>', 5)
            ->get();

        if ($failedLogins->isNotEmpty()) {
            $suspicious['failed_logins'] = $failedLogins->toArray();
        }

        // Unusual deletion activity
        $massDeletes = AuditLog::where('created_at', '>=', $startDate)
            ->where('action', 'deleted')
            ->select('user_id', 'user_type', DB::raw('COUNT(*) as delete_count'))
            ->groupBy('user_id', 'user_type')
            ->having('delete_count', '>', 10)
            ->get();

        if ($massDeletes->isNotEmpty()) {
            $suspicious['mass_deletes'] = $massDeletes->toArray();
        }

        return $suspicious;
    }

    /**
     * Get action statistics
     */
    private function getActionStatistics(\DateTime $startDate): array
    {
        return AuditLog::where('created_at', '>=', $startDate)
            ->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->pluck('count', 'action')
            ->toArray();
    }

    /**
     * Get severity statistics
     */
    private function getSeverityStatistics(\DateTime $startDate): array
    {
        return AuditLog::where('created_at', '>=', $startDate)
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->groupBy('severity')
            ->orderBy('count', 'desc')
            ->pluck('count', 'severity')
            ->toArray();
    }

    /**
     * Get user type statistics
     */
    private function getUserTypeStatistics(\DateTime $startDate): array
    {
        return AuditLog::where('created_at', '>=', $startDate)
            ->select('user_type', DB::raw('COUNT(*) as count'))
            ->groupBy('user_type')
            ->orderBy('count', 'desc')
            ->pluck('count', 'user_type')
            ->toArray();
    }

    /**
     * Get daily statistics
     */
    private function getDailyStatistics(\DateTime $startDate): array
    {
        return AuditLog::where('created_at', '>=', $startDate)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }
}