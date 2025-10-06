<?php

namespace App\Services;

use App\Services\Commands\CleanupCommandInterface;
use App\Services\Commands\CleanupAuditLogsCommand;
use App\Services\Commands\CleanupExpiredMagicLinksCommand;
use App\Services\Commands\CleanupFailedSyncsCommand;

class CleanupService
{
    private array $commands = [];

    public function __construct(
        CleanupAuditLogsCommand $auditLogsCommand,
        CleanupExpiredMagicLinksCommand $magicLinksCommand,
        CleanupFailedSyncsCommand $failedSyncsCommand
    ) {
        $this->commands = [
            'audit_logs' => $auditLogsCommand,
            'expired_magic_links' => $magicLinksCommand,
            'failed_syncs' => $failedSyncsCommand,
        ];
    }

    /**
     * Execute a cleanup operation.
     */
    public function execute(string $operation, int $days): array
    {
        if (!isset($this->commands[$operation])) {
            throw new \InvalidArgumentException("Unknown cleanup operation: {$operation}");
        }

        return $this->commands[$operation]->execute($days);
    }

    /**
     * Get available cleanup operations.
     */
    public function getAvailableOperations(): array
    {
        return array_keys($this->commands);
    }
}
