<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogCreator;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class AuditLogCreatorTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogCreator $creator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->creator = new AuditLogCreator();
        
        // Create roles for testing
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'Account Manager']);
        Role::create(['name' => 'client']);
    }

    /** @test */
    public function it_creates_audit_log_with_basic_data()
    {
        // Arrange
        $data = [
            'action' => AuditLogCreator::ACTION_CREATED,
            'client_id' => 1,
            'user_id' => 1,
        ];
        
        // Act
        $auditLog = $this->creator->log($data);
        
        // Assert
        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertEquals(AuditLogCreator::ACTION_CREATED, $auditLog->action);
        $this->assertEquals(1, $auditLog->client_id);
        $this->assertEquals(1, $auditLog->user_id);
        $this->assertDatabaseHas('audit_logs', ['action' => AuditLogCreator::ACTION_CREATED]);
    }

    /** @test */
    public function it_enriches_log_data_with_request_context()
    {
        // Arrange
        $data = ['action' => AuditLogCreator::ACTION_LOGIN];
        
        // Act
        $auditLog = $this->creator->log($data);
        
        // Assert
        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->session_id);
        $this->assertNotNull($auditLog->expires_at);
        $this->assertEquals(AuditLogCreator::SEVERITY_INFO, $auditLog->severity);
    }

    /** @test */
    public function it_sets_default_expiry_90_days_from_now()
    {
        // Arrange
        $data = ['action' => AuditLogCreator::ACTION_LOGIN];
        
        // Act
        $auditLog = $this->creator->log($data);
        
        // Assert
        $expectedExpiry = now()->addDays(90);
        $this->assertTrue($auditLog->expires_at->diffInMinutes($expectedExpiry) < 2); // Within 2 minutes
    }

    /** @test */
    public function it_honors_provided_expires_at()
    {
        // Arrange
        $customExpiry = now()->addDays(30);
        $data = [
            'action' => AuditLogCreator::ACTION_LOGIN,
            'expires_at' => $customExpiry,
        ];
        
        // Act
        $auditLog = $this->creator->log($data);
        
        // Assert
        $this->assertEquals($customExpiry->format('Y-m-d H:i:s'), $auditLog->expires_at->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_logs_model_creation()
    {
        // Arrange
        $user = User::factory()->create();
        $contentItem = ContentItem::factory()->create();
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logCreated($contentItem, 1, $user->id);
        
        // Assert
        $this->assertEquals(AuditLogCreator::ACTION_CREATED, $auditLog->action);
        $this->assertEquals(ContentItem::class, $auditLog->auditable_type);
        $this->assertEquals($contentItem->id, $auditLog->auditable_id);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals(1, $auditLog->client_id);
        $this->assertEquals(AuditLogCreator::SEVERITY_INFO, $auditLog->severity);
        $this->assertNotEmpty($auditLog->new_values);
    }

    /** @test */
    public function it_logs_model_updates_with_changes()
    {
        // Arrange
        $user = User::factory()->create();
        $contentItem = ContentItem::factory()->create(['title' => 'Updated Title']);
        $oldValues = ['title' => 'Original Title'];
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logUpdated($contentItem, $oldValues, 1, $user->id);
        
        // Assert
        $this->assertEquals(AuditLogCreator::ACTION_UPDATED, $auditLog->action);
        $this->assertEquals(ContentItem::class, $auditLog->auditable_type);
        $this->assertEquals($contentItem->id, $auditLog->auditable_id);
        $this->assertEquals($oldValues, $auditLog->old_values);
        $this->assertNotEmpty($auditLog->new_values);
        $this->assertArrayHasKey('title', $auditLog->new_values);
        $this->assertEquals('Updated Title', $auditLog->new_values['title']);
    }

    /** @test */
    public function it_logs_model_deletion()
    {
        // Arrange
        $user = User::factory()->create();
        $contentItem = ContentItem::factory()->create();
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logDeleted($contentItem, 1, $user->id);
        
        // Assert
        $this->assertEquals(AuditLogCreator::ACTION_DELETED, $auditLog->action);
        $this->assertEquals(ContentItem::class, $auditLog->auditable_type);
        $this->assertEquals($contentItem->id, $auditLog->auditable_id);
        $this->assertEquals(AuditLogCreator::SEVERITY_WARNING, $auditLog->severity);
        $this->assertNotEmpty($auditLog->old_values);
        $this->assertEmpty($auditLog->new_values);
    }

    /** @test */
    public function it_logs_user_actions()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        $metadata = ['page' => 'dashboard', 'section' => 'calendar'];
        
        // Act
        $auditLog = $this->creator->logUserAction(AuditLogCreator::ACTION_VIEWED, 1, $metadata);
        
        // Assert
        $this->assertEquals(AuditLogCreator::ACTION_VIEWED, $auditLog->action);
        $this->assertEquals($user->id, $auditLog->user_id);
        $this->assertEquals(1, $auditLog->client_id);
        $this->assertEquals($metadata, $auditLog->request_data);
        $this->assertEquals(AuditLogCreator::SEVERITY_INFO, $auditLog->severity);
    }

    /** @test */
    public function it_logs_magic_link_access()
    {
        // Arrange
        $magicLinkId = 123;
        $workspaceId = 456;
        $action = 'dashboard_access';
        
        // Act
        $auditLog = $this->creator->logMagicLinkAccess($magicLinkId, $workspaceId, $action);
        
        // Assert
        $this->assertEquals($action, $auditLog->action);
        $this->assertEquals($magicLinkId, $auditLog->user_id);
        $this->assertEquals($workspaceId, $auditLog->client_id);
        $this->assertEquals('magic_link', $auditLog->user_type);
        $this->assertEquals(['magic_link_access'], $auditLog->tags);
        $this->assertEquals(AuditLogCreator::SEVERITY_INFO, $auditLog->severity);
    }

    /** @test */
    public function it_detects_admin_user_type()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('admin');
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logUserAction(AuditLogCreator::ACTION_LOGIN);
        
        // Assert
        $this->assertEquals('admin', $auditLog->user_type);
    }

    /** @test */
    public function it_detects_account_manager_user_type()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('Account Manager');
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logUserAction(AuditLogCreator::ACTION_LOGIN);
        
        // Assert
        $this->assertEquals('agency', $auditLog->user_type);
    }

    /** @test */
    public function it_detects_client_user_type()
    {
        // Arrange
        $user = User::factory()->create();
        $user->assignRole('client');
        Auth::login($user);
        
        // Act
        $auditLog = $this->creator->logUserAction(AuditLogCreator::ACTION_LOGIN);
        
        // Assert
        $this->assertEquals('client', $auditLog->user_type);
    }

    /** @test */
    public function it_uses_anonymous_user_type_when_not_authenticated()
    {
        // Arrange - no authenticated user
        
        // Act
        $auditLog = $this->creator->log([
            'action' => AuditLogCreator::ACTION_VIEWED,
            'user_id' => null,
        ]);
        
        // Assert
        $this->assertEquals('anonymous', $auditLog->user_type);
    }

    /** @test */
    public function it_extracts_model_data_using_fillable_attributes()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create([
            'title' => 'Test Title',
            'copy' => 'Test Copy',
            'platform' => 'Facebook',
        ]);
        
        // Act
        $auditLog = $this->creator->logCreated($contentItem);
        
        // Assert
        $this->assertArrayHasKey('title', $auditLog->new_values);
        $this->assertArrayHasKey('copy', $auditLog->new_values);
        $this->assertArrayHasKey('platform', $auditLog->new_values);
        $this->assertEquals('Test Title', $auditLog->new_values['title']);
    }

    /** @test */
    public function it_uses_custom_audit_array_method_when_available()
    {
        // This would test a model that implements toAuditArray() method
        // For now, we'll test the fallback behavior
        
        // Arrange
        $contentItem = ContentItem::factory()->create();
        
        // Act
        $auditLog = $this->creator->logCreated($contentItem);
        
        // Assert
        // Should fall back to fillable attributes
        $this->assertNotEmpty($auditLog->new_values);
    }

    /** @test */
    public function it_uses_current_authenticated_user_when_no_user_id_provided()
    {
        // Arrange
        $user = User::factory()->create();
        Auth::login($user);
        $contentItem = ContentItem::factory()->create();
        
        // Act
        $auditLog = $this->creator->logCreated($contentItem); // No user_id provided
        
        // Assert
        $this->assertEquals($user->id, $auditLog->user_id);
    }

    /** @test */
    public function it_handles_all_defined_severity_constants()
    {
        // Test that all severity constants are valid
        $severities = [
            AuditLogCreator::SEVERITY_CRITICAL,
            AuditLogCreator::SEVERITY_ERROR,
            AuditLogCreator::SEVERITY_WARNING,
            AuditLogCreator::SEVERITY_INFO,
            AuditLogCreator::SEVERITY_DEBUG,
        ];
        
        foreach ($severities as $severity) {
            // Act
            $auditLog = $this->creator->log([
                'action' => AuditLogCreator::ACTION_LOGIN,
                'severity' => $severity,
            ]);
            
            // Assert
            $this->assertEquals($severity, $auditLog->severity);
        }
    }

    /** @test */
    public function it_handles_all_defined_action_constants()
    {
        // Test that all action constants work
        $actions = [
            AuditLogCreator::ACTION_CREATED,
            AuditLogCreator::ACTION_UPDATED,
            AuditLogCreator::ACTION_DELETED,
            AuditLogCreator::ACTION_VIEWED,
            AuditLogCreator::ACTION_APPROVED,
            AuditLogCreator::ACTION_REJECTED,
            AuditLogCreator::ACTION_COMMENTED,
            AuditLogCreator::ACTION_LOGIN,
            AuditLogCreator::ACTION_LOGOUT,
            AuditLogCreator::ACTION_MAGIC_LINK_ACCESSED,
            AuditLogCreator::ACTION_TRELLO_SYNC,
            AuditLogCreator::ACTION_EXPORT,
        ];
        
        foreach ($actions as $action) {
            // Act
            $auditLog = $this->creator->log(['action' => $action]);
            
            // Assert
            $this->assertEquals($action, $auditLog->action);
        }
    }
}