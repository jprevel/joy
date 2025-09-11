<?php

namespace App\Services\Audit;

use App\Constants\AuditConstants;
use App\Models\AuditLog;
use Carbon\Carbon;

class AuditCleanupService
{
    public function cleanupExpiredLogs(int $daysToKeep = AuditConstants::DEFAULT_RETENTION_DAYS): int
    {
        if ($daysToKeep < AuditConstants::MIN_CLEANUP_DAYS) {
            throw new \InvalidArgumentException("Cannot cleanup logs newer than {$this->getMinCleanupDays()} days");
        }
        
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return AuditLog::where('created_at', '<', $cutoffDate)
            ->whereNull('expires_at')
            ->orWhere('expires_at', '<', Carbon::now())
            ->delete();
    }
    
    public function getRetentionSummary(): array
    {
        $now = Carbon::now();
        
        return [
            'total_logs' => AuditLog::count(),
            'logs_last_7_days' => AuditLog::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'logs_last_30_days' => AuditLog::where('created_at', '>=', $now->copy()->subDays(30))->count(),
            'logs_last_90_days' => AuditLog::where('created_at', '>=', $now->copy()->subDays(90))->count(),
            'expired_logs' => AuditLog::where('expires_at', '<', $now)->count(),
        ];
    }
    
    private function getMinCleanupDays(): int
    {
        return AuditConstants::MIN_CLEANUP_DAYS;
    }
}