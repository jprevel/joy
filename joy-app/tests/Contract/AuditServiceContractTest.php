<?php

namespace Tests\Contract;

use Tests\TestCase;

/**
 * Contract Test: Audit Service Interface
 * Tests audit logging and compliance contracts
 */
class AuditServiceContractTest extends TestCase
{
    /** @test */
    public function it_should_define_audit_logging_interface()
    {
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'log'));
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'logModelCreated'));
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'logModelUpdated'));
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'logModelDeleted'));
    }

    /** @test */
    public function it_should_handle_data_retention_contracts()
    {
        $auditLog = new \App\Models\AuditLog();
        $fillable = $auditLog->getFillable();
        $this->assertContains('event', $fillable);
        $this->assertContains('old_values', array_keys($auditLog->getCasts()));
        $this->assertTrue($auditLog->usesTimestamps());
    }

    /** @test */
    public function it_should_provide_search_and_analysis_contract()
    {
        $auditLog = new \App\Models\AuditLog();

        $this->assertTrue(method_exists($auditLog, 'scopeForEvent'));
        $this->assertTrue(method_exists($auditLog, 'scopeRecent'));

        $this->assertContains('event', $auditLog->getFillable());
        $this->assertContains('user_id', $auditLog->getFillable());
    }
}