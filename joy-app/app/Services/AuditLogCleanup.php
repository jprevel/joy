<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class AuditLogCleanup
{
    /**
     * Clean up expired audit logs
     */
    public function cleanupExpired(): int
    {
        return AuditLog::expired()->delete();
    }

    /**
     * Clean up old audit logs by age
     */
    public function cleanupByAge(int $days): int
    {
        $cutoffDate = now()->subDays($days);
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Clean up audit logs by severity (keeping only critical and error logs)
     */
    public function cleanupBySeverity(array $severities = ['debug', 'info']): int
    {
        return AuditLog::whereIn('severity', $severities)->delete();
    }

    /**
     * Archive old audit logs to a different table or export them
     */
    public function archiveOldLogs(int $days): array
    {
        $cutoffDate = now()->subDays($days);
        $logsToArchive = AuditLog::where('created_at', '<', $cutoffDate)->get();
        
        $archiveData = $logsToArchive->toArray();
        $deletedCount = AuditLog::where('created_at', '<', $cutoffDate)->delete();
        
        return [
            'archived_count' => count($archiveData),
            'deleted_count' => $deletedCount,
            'archive_data' => $archiveData,
        ];
    }

    /**
     * Optimize audit log table (rebuild indexes, analyze)
     */
    public function optimizeTable(): void
    {
        $tableName = (new AuditLog())->getTable();
        
        // For MySQL
        if (config('database.default') === 'mysql') {
            DB::statement("OPTIMIZE TABLE {$tableName}");
        }
        
        // For PostgreSQL
        if (config('database.default') === 'pgsql') {
            DB::statement("VACUUM ANALYZE {$tableName}");
        }
    }

    /**
     * Get cleanup recommendations based on current data
     */
    public function getCleanupRecommendations(): array
    {
        $totalLogs = AuditLog::count();
        $expiredLogs = AuditLog::expired()->count();
        $oldLogs = AuditLog::where('created_at', '<', now()->subDays(365))->count();
        $debugLogs = AuditLog::where('severity', 'debug')->count();
        
        $recommendations = [];
        
        if ($expiredLogs > 0) {
            $recommendations[] = [
                'type' => 'expired',
                'count' => $expiredLogs,
                'action' => 'Delete expired audit logs',
                'priority' => 'high',
            ];
        }
        
        if ($oldLogs > 1000) {
            $recommendations[] = [
                'type' => 'old_logs',
                'count' => $oldLogs,
                'action' => 'Archive logs older than 1 year',
                'priority' => 'medium',
            ];
        }
        
        if ($debugLogs > 5000) {
            $recommendations[] = [
                'type' => 'debug_logs',
                'count' => $debugLogs,
                'action' => 'Clean up debug severity logs',
                'priority' => 'low',
            ];
        }
        
        if ($totalLogs > 100000) {
            $recommendations[] = [
                'type' => 'table_optimization',
                'count' => $totalLogs,
                'action' => 'Optimize audit log table for better performance',
                'priority' => 'medium',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Execute cleanup based on configuration
     */
    public function executeCleanup(array $config = []): array
    {
        $results = [
            'expired_deleted' => 0,
            'old_deleted' => 0,
            'severity_deleted' => 0,
            'total_deleted' => 0,
        ];
        
        // Clean up expired logs
        if ($config['cleanup_expired'] ?? true) {
            $results['expired_deleted'] = $this->cleanupExpired();
        }
        
        // Clean up old logs
        if (isset($config['cleanup_days'])) {
            $results['old_deleted'] = $this->cleanupByAge($config['cleanup_days']);
        }
        
        // Clean up by severity
        if (isset($config['cleanup_severities'])) {
            $results['severity_deleted'] = $this->cleanupBySeverity($config['cleanup_severities']);
        }
        
        // Optimize table
        if ($config['optimize_table'] ?? false) {
            $this->optimizeTable();
        }
        
        $results['total_deleted'] = $results['expired_deleted'] + $results['old_deleted'] + $results['severity_deleted'];
        
        return $results;
    }

    /**
     * Get storage size information
     */
    public function getStorageInfo(): array
    {
        $tableName = (new AuditLog())->getTable();
        
        // For MySQL
        if (config('database.default') === 'mysql') {
            $result = DB::selectOne("
                SELECT 
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    ROUND((data_length / 1024 / 1024), 2) AS data_size_mb,
                    ROUND((index_length / 1024 / 1024), 2) AS index_size_mb
                FROM information_schema.TABLES 
                WHERE table_schema = DATABASE() 
                AND table_name = ?
            ", [$tableName]);
            
            return [
                'total_size_mb' => $result->size_mb ?? 0,
                'data_size_mb' => $result->data_size_mb ?? 0,
                'index_size_mb' => $result->index_size_mb ?? 0,
            ];
        }
        
        // For PostgreSQL and others, return basic info
        return [
            'total_records' => AuditLog::count(),
            'estimated_size_mb' => AuditLog::count() * 0.001, // Rough estimate
        ];
    }

    /**
     * Schedule automatic cleanup
     */
    public function scheduleCleanup(): void
    {
        // This would typically be called from a scheduled job
        $config = [
            'cleanup_expired' => true,
            'cleanup_days' => config('audit.cleanup_days', 365),
            'cleanup_severities' => config('audit.cleanup_severities', ['debug']),
            'optimize_table' => true,
        ];
        
        $this->executeCleanup($config);
    }
}