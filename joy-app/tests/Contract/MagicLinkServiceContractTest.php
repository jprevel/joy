<?php

namespace Tests\Contract;

use Tests\TestCase;

/**
 * Contract Test: MagicLink Security Interface
 * Tests authentication and security contracts
 */
class MagicLinkServiceContractTest extends TestCase
{
    /** @test */
    public function it_should_define_secure_token_generation_contract()
    {
        $service = app(\App\Services\MagicLinkService::class);

        $this->assertTrue(method_exists($service, 'generateMagicLink'));
        $this->assertTrue(method_exists($service, 'validateToken'));

        $reflection = new \ReflectionMethod($service, 'generateMagicLink');
        $this->assertEquals(5, $reflection->getNumberOfParameters());
    }

    /** @test */
    public function it_should_validate_expiration_handling_contract()
    {
        $magicLink = new \App\Models\MagicLink();

        $this->assertTrue(method_exists($magicLink, 'isValid'));
        $this->assertTrue(in_array('expires_at', $magicLink->getFillable()));
        $this->assertTrue(in_array('accessed_at', $magicLink->getFillable()));
    }

    /** @test */
    public function it_should_enforce_access_control_contracts()
    {
        $service = app(\App\Services\MagicLinkService::class);

        $this->assertTrue(method_exists($service, 'getAccessUrl'));

        $magicLink = new \App\Models\MagicLink();
        $this->assertTrue(in_array('scopes', $magicLink->getFillable()));
    }

    /** @test */
    public function it_should_audit_access_attempts_contract()
    {
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'log'));
        $this->assertTrue(method_exists(\App\Services\AuditService::class, 'logMagicLinkAccessed'));

        $auditLog = new \App\Models\AuditLog();
        $this->assertTrue(in_array('event', $auditLog->getFillable()));
    }
}