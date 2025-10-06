<?php

namespace App\Services\Commands;

use App\Services\AuditService;

class CleanupAuditLogsCommand implements CleanupCommandInterface
{
    public function __construct(
        private AuditService $auditService
    ) {}

    /**
     * Execute audit logs cleanup.
     */
    public function execute(int $days): array
    {
        $deleted = $this->auditService->cleanupOldLogs($days);

        return [
            'deleted_count' => $deleted,
            'operation' => $this->getName()
        ];
    }

    /**
     * Get the operation name.
     */
    public function getName(): string
    {
        return 'audit_logs';
    }
}
