<?php

namespace Tests\Unit\Traits;

use Tests\TestCase;
use App\Traits\HasRoleManagement;
use App\Services\RoleDetectionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Mockery;

class HasRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private $traitObject;
    private $mockRoleService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create anonymous class that uses the trait for testing
        $this->traitObject = new class {
            use HasRoleManagement;
            
            public $currentRole = null;
        };
        
        // Mock the RoleDetectionService
        $this->mockRoleService = Mockery::mock(RoleDetectionService::class);
        $this->app->instance(RoleDetectionService::class, $this->mockRoleService);
    }

    /** @test */
    public function it_gets_current_user_role_through_service()
    {
        // Arrange
        $user = User::factory()->create();
        $this->traitObject->currentRole = 'admin';
        
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->once()
            ->with('admin')
            ->andReturn($user);
        
        // Act
        $result = $this->traitObject->getCurrentUserRole();
        
        // Assert
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals($user->id, $result->id);
    }

    /** @test */
    public function it_gets_current_user_role_with_null_current_role()
    {
        // Arrange
        $this->traitObject->currentRole = null;
        
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->once()
            ->with(null)
            ->andReturn(null);
        
        // Act
        $result = $this->traitObject->getCurrentUserRole();
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_checks_permission_through_service()
    {
        // Arrange
        $this->traitObject->currentRole = 'agency';
        
        $this->mockRoleService
            ->shouldReceive('hasPermission')
            ->once()
            ->with('edit content', 'agency')
            ->andReturn(true);
        
        // Act
        $result = $this->traitObject->hasPermission('edit content');
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_false_for_missing_permission()
    {
        // Arrange
        $this->traitObject->currentRole = 'client';
        
        $this->mockRoleService
            ->shouldReceive('hasPermission')
            ->once()
            ->with('admin action', 'client')
            ->andReturn(false);
        
        // Act
        $result = $this->traitObject->hasPermission('admin action');
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_handles_object_without_current_role_property()
    {
        // Arrange - Create object without currentRole property
        $objectWithoutRole = new class {
            use HasRoleManagement;
        };
        
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->once()
            ->with(null) // Should pass null when property doesn't exist
            ->andReturn(null);
        
        // Act
        $result = $objectWithoutRole->getCurrentUserRole();
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_passes_current_role_to_permission_check()
    {
        // Arrange
        $this->traitObject->currentRole = 'admin';
        
        $this->mockRoleService
            ->shouldReceive('hasPermission')
            ->once()
            ->with('delete user', 'admin')
            ->andReturn(true);
        
        // Act
        $result = $this->traitObject->hasPermission('delete user');
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_works_with_different_role_values()
    {
        $roles = ['admin', 'agency', 'client', null, ''];
        
        foreach ($roles as $role) {
            // Arrange
            $this->traitObject->currentRole = $role;
            
            $this->mockRoleService
                ->shouldReceive('getCurrentUserRole')
                ->once()
                ->with($role)
                ->andReturn(null);
            
            $this->mockRoleService
                ->shouldReceive('hasPermission')
                ->once()
                ->with('test permission', $role)
                ->andReturn(false);
            
            // Act & Assert
            $userResult = $this->traitObject->getCurrentUserRole();
            $permissionResult = $this->traitObject->hasPermission('test permission');
            
            $this->assertNull($userResult);
            $this->assertFalse($permissionResult);
        }
    }

    /** @test */
    public function it_delegates_correctly_to_service_methods()
    {
        // This test ensures the trait is purely a delegation layer
        // and doesn't contain business logic
        
        // Arrange
        $testUser = User::factory()->create();
        $this->traitObject->currentRole = 'test_role';
        
        // Mock service calls
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->with('test_role')
            ->andReturn($testUser);
        
        $this->mockRoleService
            ->shouldReceive('hasPermission')
            ->with('test_permission', 'test_role')
            ->andReturn(true);
        
        // Act
        $userResult = $this->traitObject->getCurrentUserRole();
        $permissionResult = $this->traitObject->hasPermission('test_permission');
        
        // Assert - Verify delegation works correctly
        $this->assertEquals($testUser, $userResult);
        $this->assertTrue($permissionResult);
        
        // Verify service was called correctly
        $this->mockRoleService->shouldHaveReceived('getCurrentUserRole')->once();
        $this->mockRoleService->shouldHaveReceived('hasPermission')->once();
    }

    /** @test */
    public function it_can_be_used_by_multiple_classes()
    {
        // Arrange - Create multiple classes using the trait
        $object1 = new class {
            use HasRoleManagement;
            public $currentRole = 'admin';
        };
        
        $object2 = new class {
            use HasRoleManagement;
            public $currentRole = 'client';
        };
        
        // Mock service responses for different roles
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->with('admin')
            ->andReturn(User::factory()->create());
        
        $this->mockRoleService
            ->shouldReceive('getCurrentUserRole')
            ->with('client')
            ->andReturn(User::factory()->create());
        
        // Act
        $result1 = $object1->getCurrentUserRole();
        $result2 = $object2->getCurrentUserRole();
        
        // Assert
        $this->assertInstanceOf(User::class, $result1);
        $this->assertInstanceOf(User::class, $result2);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}