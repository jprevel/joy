<?php

namespace App\Console\Commands;

use App\Services\AuditService;
use App\DTOs\AuditLogRequest;
use Illuminate\Console\Command;

class CleanupAuditLogs extends Command
{
    protected $signature = 'audit:cleanup {--days=90 : Number of days to keep audit logs}';

    protected $description = 'Clean up old audit logs to maintain database performance';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        
        if ($days < 30) {
            $this->error('Cannot delete logs newer than 30 days for compliance reasons.');
            return Command::FAILURE;
        }

        $this->info("Cleaning up audit logs older than {$days} days...");
        
        $deletedCount = AuditService::cleanupOldLogs($days);
        
        if ($deletedCount > 0) {
            $this->info("Successfully deleted {$deletedCount} old audit log records.");
            
            // Log the cleanup action
            AuditService::log(
                AuditLogRequest::create('audit_cleanup')
                    ->withNewValues([
                        'days_kept' => $days,
                        'records_deleted' => $deletedCount,
                        'triggered_by' => 'artisan_command',
                    ])
                    ->withSeverity('info')
                    ->withTags(['system_cleanup', 'automated'])
            );
        } else {
            $this->info('No old audit logs found to delete.');
        }

        return Command::SUCCESS;
    }
}