<?php

namespace App\Services;

use App\Constants\AuditConstants;
use App\DTOs\AuditLogData;
use App\DTOs\AuditLogRequest;
use App\Models\AuditLog;
use App\Models\MagicLink;
use App\Services\Audit\AuditLogger;
use App\Services\Audit\AuditModelTracker;
use App\Services\Audit\AuditSecurityTracker;
use App\Services\Audit\AuditCleanupService;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    private static ?AuditLogger $logger = null;
    private static ?AuditModelTracker $modelTracker = null;
    private static ?AuditSecurityTracker $securityTracker = null;
    private static ?AuditCleanupService $cleanupService = null;
    
    public static function log(AuditLogRequest|string $request, ...$legacyArgs): AuditLog {
        // Handle new parameter object approach
        if ($request instanceof AuditLogRequest) {
            return self::getLogger()->log($request->toAuditLogData());
        }
        
        // Legacy support - convert old parameters to AuditLogRequest
        $action = $request; // $request is string in legacy mode
        $auditable = $legacyArgs[0] ?? null;
        $oldValues = $legacyArgs[1] ?? [];
        $newValues = $legacyArgs[2] ?? [];
        $workspaceId = $legacyArgs[3] ?? null;
        $userId = $legacyArgs[4] ?? null;
        $userType = $legacyArgs[5] ?? null;
        $severity = $legacyArgs[6] ?? AuditConstants::SEVERITY_INFO;
        $tags = $legacyArgs[7] ?? [];
        $requestData = $legacyArgs[8] ?? null;
        $responseData = $legacyArgs[9] ?? null;
        $data = new AuditLogData(
            action: $action,
            auditable: $auditable,
            oldValues: $oldValues,
            newValues: $newValues,
            workspaceId: $workspaceId,
            userId: $userId,
            userType: $userType,
            severity: $severity,
            tags: $tags,
            requestData: $requestData,
            responseData: $responseData
        );
        
        return self::getLogger()->log($data);
    }

    public static function logModelCreated(Model $model, array $tags = []): AuditLog
    {
        return self::getModelTracker()->logModelCreated($model, $tags);
    }

    public static function logModelUpdated(Model $model, array $originalValues = [], array $tags = []): AuditLog
    {
        return self::getModelTracker()->logModelUpdated($model, $originalValues, $tags);
    }

    public static function logModelDeleted(Model $model, array $tags = []): AuditLog
    {
        return self::getModelTracker()->logModelDeleted($model, $tags);
    }

    public static function logMagicLinkAccessed(MagicLink $magicLink, array $tags = []): AuditLog
    {
        return self::getModelTracker()->logMagicLinkAccessed($magicLink, $tags);
    }

    public static function logCommentAdded(Model $comment, Model $variant, array $tags = []): AuditLog
    {
        return self::getModelTracker()->logCommentCreated($comment, $variant, $tags);
    }

    public static function logSecurityEvent(
        string $event,
        string $severity = AuditConstants::SEVERITY_WARNING,
        ?int $workspaceId = null,
        array $details = [],
        array $tags = []
    ): AuditLog {
        return self::getSecurityTracker()->logSecurityEvent($event, $severity, $workspaceId, $details, $tags);
    }

    public static function logExport(
        string $exportType,
        array $filters = [],
        ?int $recordCount = null,
        array $tags = []
    ): AuditLog {
        return self::getSecurityTracker()->logDataExport($exportType, $filters, $recordCount ?? 0);
    }

    public static function logTrelloSync(array $results, array $tags = []): AuditLog
    {
        return self::getSecurityTracker()->logSyncOperation('trello', $results);
    }

    public static function cleanupOldLogs(int $daysToKeep = AuditConstants::DEFAULT_RETENTION_DAYS): int
    {
        return self::getCleanupService()->cleanupExpiredLogs($daysToKeep);
    }

    public static function getRetentionSummary(): array
    {
        return self::getCleanupService()->getRetentionSummary();
    }

    public static function getRecentActivity(
        ?int $workspaceId = null,
        int $limit = AuditConstants::PAGINATION_LIMIT,
        int $days = 7
    ): \Illuminate\Database\Eloquent\Collection {
        $query = AuditLog::with(['workspace'])
            ->recent($days)
            ->latest()
            ->limit($limit);

        if ($workspaceId) {
            $query->forWorkspace($workspaceId);
        }

        return $query->get();
    }

    public static function getUserActivity(
        int $userId,
        ?string $userType = null,
        int $limit = AuditConstants::PAGINATION_LIMIT,
        int $days = 30
    ): \Illuminate\Database\Eloquent\Collection {
        return AuditLog::with(['workspace'])
            ->forUser($userId, $userType)
            ->recent($days)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function getModelHistory(
        Model $model,
        int $limit = 100
    ): \Illuminate\Database\Eloquent\Collection {
        return AuditLog::with(['workspace'])
            ->forModel(get_class($model), $model->id)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public static function generateReport(?int $workspaceId = null, int $days = 30): array
    {
        $query = AuditLog::recent($days);

        if ($workspaceId) {
            $query->forWorkspace($workspaceId);
        }

        $logs = $query->get();

        return [
            'period' => "{$days} days",
            'total_events' => $logs->count(),
            'events_by_action' => $logs->groupBy('action')->map->count(),
            'events_by_severity' => $logs->groupBy('severity')->map->count(),
            'events_by_user_type' => $logs->groupBy('user_type')->map->count(),
            'unique_users' => $logs->whereNotNull('user_id')->pluck('user_id')->unique()->count(),
            'unique_ips' => $logs->whereNotNull('ip_address')->pluck('ip_address')->unique()->count(),
            'security_events' => $logs->where('tags', 'like', '%security%')->count(),
            'errors' => $logs->where('severity', AuditConstants::SEVERITY_ERROR)->count(),
        ];
    }

    private static function getLogger(): AuditLogger
    {
        return self::$logger ??= new AuditLogger();
    }

    private static function getModelTracker(): AuditModelTracker
    {
        return self::$modelTracker ??= new AuditModelTracker(self::getLogger());
    }

    private static function getSecurityTracker(): AuditSecurityTracker
    {
        return self::$securityTracker ??= new AuditSecurityTracker(self::getLogger());
    }

    private static function getCleanupService(): AuditCleanupService
    {
        return self::$cleanupService ??= new AuditCleanupService();
    }
}