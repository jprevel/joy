<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RoleDetectionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class RoleDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = new RoleDetectionService();
        
        // Create roles for testing
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'Account Manager']);
        Role::create(['name' => 'agency']);
        Role::create(['name' => 'client']);
    }

    /** @test */
    public function it_returns_authenticated_user_when_logged_in()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act
        $result = $this->service->getCurrentUserRole();
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_user_and_no_fallback()
    {
        // Arrange - no authenticated user
        
        // Act
        $result = $this->service->getCurrentUserRole();
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_demo_user_for_fallback_role()
    {
        // Arrange
        $adminUser = User::factory()->create();
        $adminUser->assignRole('admin');
        
        // Act
        $result = $this->service->getCurrentUserRole('admin');
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertTrue($result->hasRole('admin'));
    }

    /** @test */
    public function it_detects_admin_role_correctly()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole();
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_ADMIN, $role);
    }

    /** @test */
    public function it_detects_agency_role_for_account_manager()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('Account Manager');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole();
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $role);
    }

    /** @test */
    public function it_detects_agency_role_for_agency_user()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('agency');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole();
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $role);
    }

    /** @test */
    public function it_defaults_to_client_role()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole();
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_CLIENT, $role);
    }

    /** @test */
    public function it_honors_requested_role_when_user_has_access()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole(RoleDetectionService::ROLE_AGENCY);
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $role);
    }

    /** @test */
    public function it_ignores_requested_role_when_user_lacks_access()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        Auth::login($user);
        
        // Act
        $role = $this->service->detectRole(RoleDetectionService::ROLE_ADMIN);
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_CLIENT, $role);
    }

    /** @test */
    public function it_returns_requested_role_when_not_authenticated()
    {
        // Arrange - no authenticated user
        
        // Act
        $role = $this->service->detectRole(RoleDetectionService::ROLE_AGENCY);
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $role);
    }

    /** @test */
    public function it_gets_primary_role_for_admin_user()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole(['admin', 'client']); // Multiple roles, admin should win
        
        // Act
        $role = $this->service->getUserPrimaryRole($user);
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_ADMIN, $role);
    }

    /** @test */
    public function it_gets_primary_role_for_account_manager()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole(['Account Manager', 'client']);
        
        // Act
        $role = $this->service->getUserPrimaryRole($user);
        
        // Assert
        $this->assertEquals(RoleDetectionService::ROLE_AGENCY, $role);
    }

    /** @test */
    public function it_checks_admin_can_access_all_roles()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        // Act & Assert
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_ADMIN));
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_CLIENT));
    }

    /** @test */
    public function it_checks_account_manager_can_access_agency_and_client()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('Account Manager');
        
        // Act & Assert
        $this->assertFalse($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_ADMIN));
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_CLIENT));
    }

    /** @test */
    public function it_checks_client_can_only_access_client_role()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        
        // Act & Assert
        $this->assertFalse($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_ADMIN));
        $this->assertFalse($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_AGENCY));
        $this->assertTrue($this->service->userCanAccessRole($user, RoleDetectionService::ROLE_CLIENT));
    }

    /** @test */
    public function it_gets_available_roles_for_admin()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        // Act
        $roles = $this->service->getAvailableRoles($user);
        
        // Assert
        $this->assertContains(RoleDetectionService::ROLE_ADMIN, $roles);
        $this->assertContains(RoleDetectionService::ROLE_AGENCY, $roles);
        $this->assertContains(RoleDetectionService::ROLE_CLIENT, $roles);
    }

    /** @test */
    public function it_gets_available_roles_for_account_manager()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('Account Manager');
        
        // Act
        $roles = $this->service->getAvailableRoles($user);
        
        // Assert
        $this->assertNotContains(RoleDetectionService::ROLE_ADMIN, $roles);
        $this->assertContains(RoleDetectionService::ROLE_AGENCY, $roles);
        $this->assertContains(RoleDetectionService::ROLE_CLIENT, $roles);
    }

    /** @test */
    public function it_gets_available_roles_for_client()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        
        // Act
        $roles = $this->service->getAvailableRoles($user);
        
        // Assert
        $this->assertNotContains(RoleDetectionService::ROLE_ADMIN, $roles);
        $this->assertNotContains(RoleDetectionService::ROLE_AGENCY, $roles);
        $this->assertContains(RoleDetectionService::ROLE_CLIENT, $roles);
    }

    /** @test */
    public function it_gets_role_display_names()
    {
        // Act & Assert
        $this->assertEquals('Administrator', $this->service->getRoleDisplayName(RoleDetectionService::ROLE_ADMIN));
        $this->assertEquals('Agency Team', $this->service->getRoleDisplayName(RoleDetectionService::ROLE_AGENCY));
        $this->assertEquals('Client', $this->service->getRoleDisplayName(RoleDetectionService::ROLE_CLIENT));
        $this->assertEquals('Custom', $this->service->getRoleDisplayName('custom'));
    }

    /** @test */
    public function it_gets_default_route_for_admin()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        
        // Act
        $route = $this->service->getDefaultRoute($user);
        
        // Assert
        $this->assertEquals('/admin', $route);
    }

    /** @test */
    public function it_gets_default_route_for_account_manager()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('Account Manager');
        
        // Act
        $route = $this->service->getDefaultRoute($user);
        
        // Assert
        $this->assertEquals('/calendar/agency', $route);
    }

    /** @test */
    public function it_gets_default_route_for_client()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        
        // Act
        $route = $this->service->getDefaultRoute($user);
        
        // Assert
        $this->assertEquals('/calendar/client', $route);
    }

    /** @test */
    public function it_returns_login_route_when_no_user()
    {
        // Act
        $route = $this->service->getDefaultRoute();
        
        // Assert
        $this->assertEquals('/login', $route);
    }

    /** @test */
    public function it_checks_permissions_for_authenticated_user()
    {
        // Arrange
        $user = User::factory()->create();
        $user->givePermissionTo('edit content');
        Auth::login($user);
        
        // Act
        $hasPermission = $this->service->hasPermission('edit content');
        
        // Assert
        $this->assertTrue($hasPermission);
    }

    /** @test */
    public function it_returns_false_for_missing_permission()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        
        // Act
        $hasPermission = $this->service->hasPermission('non-existent permission');
        
        // Assert
        $this->assertFalse($hasPermission);
    }

    /** @test */
    public function it_returns_false_when_no_user_for_permission_check()
    {
        // Act
        $hasPermission = $this->service->hasPermission('any permission');
        
        // Assert
        $this->assertFalse($hasPermission);
    }
}