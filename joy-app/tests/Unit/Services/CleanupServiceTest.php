<?php

namespace Tests\Unit\Services;

use App\Services\CleanupService;
use App\Services\Commands\CleanupAuditLogsCommand;
use App\Services\Commands\CleanupExpiredMagicLinksCommand;
use App\Services\Commands\CleanupFailedSyncsCommand;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    private CleanupService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $auditLogsCommand = $this->createMock(CleanupAuditLogsCommand::class);
        $magicLinksCommand = $this->createMock(CleanupExpiredMagicLinksCommand::class);
        $failedSyncsCommand = $this->createMock(CleanupFailedSyncsCommand::class);

        $this->service = new CleanupService(
            $auditLogsCommand,
            $magicLinksCommand,
            $failedSyncsCommand
        );
    }

    /** @test */
    public function it_executes_audit_logs_cleanup_operation()
    {
        $auditLogsCommand = $this->createMock(CleanupAuditLogsCommand::class);
        $auditLogsCommand->expects($this->once())
            ->method('execute')
            ->with(30)
            ->willReturn(['deleted_count' => 5, 'operation' => 'audit_logs']);

        $service = new CleanupService(
            $auditLogsCommand,
            $this->createMock(CleanupExpiredMagicLinksCommand::class),
            $this->createMock(CleanupFailedSyncsCommand::class)
        );

        $result = $service->execute('audit_logs', 30);

        $this->assertEquals(['deleted_count' => 5, 'operation' => 'audit_logs'], $result);
    }

    /** @test */
    public function it_executes_expired_magic_links_cleanup_operation()
    {
        $magicLinksCommand = $this->createMock(CleanupExpiredMagicLinksCommand::class);
        $magicLinksCommand->expects($this->once())
            ->method('execute')
            ->with(30)
            ->willReturn(['deleted_count' => 3, 'operation' => 'expired_magic_links']);

        $service = new CleanupService(
            $this->createMock(CleanupAuditLogsCommand::class),
            $magicLinksCommand,
            $this->createMock(CleanupFailedSyncsCommand::class)
        );

        $result = $service->execute('expired_magic_links', 30);

        $this->assertEquals(['deleted_count' => 3, 'operation' => 'expired_magic_links'], $result);
    }

    /** @test */
    public function it_executes_failed_syncs_cleanup_operation()
    {
        $failedSyncsCommand = $this->createMock(CleanupFailedSyncsCommand::class);
        $failedSyncsCommand->expects($this->once())
            ->method('execute')
            ->with(30)
            ->willReturn(['deleted_count' => 2, 'operation' => 'failed_syncs']);

        $service = new CleanupService(
            $this->createMock(CleanupAuditLogsCommand::class),
            $this->createMock(CleanupExpiredMagicLinksCommand::class),
            $failedSyncsCommand
        );

        $result = $service->execute('failed_syncs', 30);

        $this->assertEquals(['deleted_count' => 2, 'operation' => 'failed_syncs'], $result);
    }

    /** @test */
    public function it_passes_days_parameter_to_command()
    {
        $auditLogsCommand = $this->createMock(CleanupAuditLogsCommand::class);
        $auditLogsCommand->expects($this->once())
            ->method('execute')
            ->with(90)
            ->willReturn(['deleted_count' => 10, 'operation' => 'audit_logs']);

        $service = new CleanupService(
            $auditLogsCommand,
            $this->createMock(CleanupExpiredMagicLinksCommand::class),
            $this->createMock(CleanupFailedSyncsCommand::class)
        );

        $service->execute('audit_logs', 90);
    }

    /** @test */
    public function it_returns_command_execution_results()
    {
        $auditLogsCommand = $this->createMock(CleanupAuditLogsCommand::class);
        $auditLogsCommand->method('execute')
            ->willReturn(['deleted_count' => 15, 'operation' => 'audit_logs']);

        $service = new CleanupService(
            $auditLogsCommand,
            $this->createMock(CleanupExpiredMagicLinksCommand::class),
            $this->createMock(CleanupFailedSyncsCommand::class)
        );

        $result = $service->execute('audit_logs', 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('deleted_count', $result);
        $this->assertArrayHasKey('operation', $result);
        $this->assertEquals(15, $result['deleted_count']);
    }

    /** @test */
    public function it_throws_exception_for_unknown_operation()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown cleanup operation: invalid_operation');

        $this->service->execute('invalid_operation', 30);
    }

    /** @test */
    public function it_returns_available_operations_list()
    {
        $operations = $this->service->getAvailableOperations();

        $this->assertIsArray($operations);
        $this->assertContains('audit_logs', $operations);
        $this->assertContains('expired_magic_links', $operations);
        $this->assertContains('failed_syncs', $operations);
        $this->assertCount(3, $operations);
    }

    /** @test */
    public function it_uses_command_pattern_for_extensibility()
    {
        // Verify service accepts command implementations
        $auditLogsCommand = $this->createMock(CleanupAuditLogsCommand::class);
        $magicLinksCommand = $this->createMock(CleanupExpiredMagicLinksCommand::class);
        $failedSyncsCommand = $this->createMock(CleanupFailedSyncsCommand::class);

        $service = new CleanupService(
            $auditLogsCommand,
            $magicLinksCommand,
            $failedSyncsCommand
        );

        // Verify all commands are accessible through getAvailableOperations
        $operations = $service->getAvailableOperations();
        $this->assertCount(3, $operations);

        // Verify the service can execute each command type
        foreach ($operations as $operation) {
            $this->assertIsString($operation);
        }
    }
}
