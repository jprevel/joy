<?php

namespace App\Services\Audit;

use App\Constants\AuditConstants;
use App\DTOs\AuditLogData;
use App\Models\AuditLog;

class AuditSecurityTracker
{
    public function __construct(
        private AuditLogger $auditLogger
    ) {}
    
    public function logSecurityEvent(
        string $action, 
        string $severity = AuditConstants::SEVERITY_WARNING,
        ?int $workspaceId = null,
        array $contextData = [],
        array $tags = []
    ): AuditLog {
        $data = AuditLogData::create($action)
            ->withSeverity($severity)
            ->withWorkspace($workspaceId)
            ->withRequestData($contextData)
            ->withTags(array_merge([AuditConstants::TAG_SECURITY], $tags));
            
        return $this->auditLogger->log($data);
    }
    
    public function logUnauthorizedAccess(string $reason, array $context = []): AuditLog
    {
        return $this->logSecurityEvent(
            'unauthorized_access_attempt',
            AuditConstants::SEVERITY_WARNING,
            null,
            array_merge(['reason' => $reason], $context),
            ['unauthorized', 'access']
        );
    }
    
    public function logAdminAccess(array $context = []): AuditLog
    {
        return $this->logSecurityEvent(
            'admin_access_granted',
            AuditConstants::SEVERITY_INFO,
            null,
            $context,
            [AuditConstants::TAG_ADMIN_ACCESS]
        );
    }
    
    public function logDataExport(string $type, array $filters, int $recordCount): AuditLog
    {
        return $this->logSecurityEvent(
            'data_export',
            AuditConstants::SEVERITY_INFO,
            null,
            [
                'export_type' => $type,
                'filters' => $filters,
                'record_count' => $recordCount,
            ],
            [AuditConstants::TAG_EXPORT]
        );
    }
    
    public function logSyncOperation(string $service, array $results): AuditLog
    {
        $severity = !empty($results['errors']) ? AuditConstants::SEVERITY_WARNING : AuditConstants::SEVERITY_INFO;
        
        return $this->logSecurityEvent(
            'sync_completed',
            $severity,
            null,
            [
                'service' => $service,
                'results' => $results,
            ],
            [AuditConstants::TAG_SYNC]
        );
    }
}