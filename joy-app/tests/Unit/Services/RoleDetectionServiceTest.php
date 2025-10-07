<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\RoleDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Test: RoleDetectionService
 * Tests user role detection and permission logic
 */
class RoleDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RoleDetectionService();
    }

    /** @test */
    public function it_detects_admin_roles()
    {
        $adminUser = User::factory()->admin()->create();

        $primaryRole = $this->service->getUserPrimaryRole($adminUser);

        $this->assertEquals(RoleDetectionService::ROLE_ADMIN, $primaryRole);
        $this->assertTrue($this->service->userCanAccessRole($adminUser, RoleDetectionService::ROLE_ADMIN));
    }

    /** @test */
    public function it_detects_agency_roles()
    {
        $agencyUser = User::factory()->agency()->create();

        $primaryRole = $this->service->getUserPrimaryRole($agencyUser);

        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $primaryRole);
        $this->assertTrue($this->service->userCanAccessRole($agencyUser, RoleDetectionService::ROLE_AGENCY));
    }

    /** @test */
    public function it_detects_client_roles()
    {
        $clientUser = User::factory()->client()->create();

        $primaryRole = $this->service->getUserPrimaryRole($clientUser);

        $this->assertEquals(RoleDetectionService::ROLE_CLIENT, $primaryRole);
        $this->assertTrue($this->service->userCanAccessRole($clientUser, RoleDetectionService::ROLE_CLIENT));
    }

    /** @test */
    public function it_handles_role_permission_mapping()
    {
        $adminUser = User::factory()->admin()->create();
        $agencyUser = User::factory()->agency()->create();
        $clientUser = User::factory()->client()->create();

        // Admin can access all roles
        $this->assertTrue($this->service->userCanAccessRole($adminUser, RoleDetectionService::ROLE_ADMIN));
        $this->assertTrue($this->service->userCanAccessRole($adminUser, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($adminUser, RoleDetectionService::ROLE_CLIENT));

        // Agency can access agency and client roles
        $this->assertFalse($this->service->userCanAccessRole($agencyUser, RoleDetectionService::ROLE_ADMIN));
        $this->assertTrue($this->service->userCanAccessRole($agencyUser, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($agencyUser, RoleDetectionService::ROLE_CLIENT));

        // Client can only access client role
        $this->assertFalse($this->service->userCanAccessRole($clientUser, RoleDetectionService::ROLE_ADMIN));
        $this->assertFalse($this->service->userCanAccessRole($clientUser, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($clientUser, RoleDetectionService::ROLE_CLIENT));
    }
}
