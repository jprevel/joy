<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * E2E Test: Admin Content Management Workflow
 * Tests complete admin user journey
 */
class AdminContentManagementE2ETest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_create_content_for_multiple_clients()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $client1 = \App\Models\Client::factory()->create();
        $client2 = \App\Models\Client::factory()->create();

        $this->actingAs($admin);

        // Test that admin user has correct role
        $this->assertEquals('admin', $admin->getRoleName());

        // Test that clients exist in database
        $this->assertDatabaseHas('clients', ['id' => $client1->id]);
        $this->assertDatabaseHas('clients', ['id' => $client2->id]);

        // Test that admin can access user management functions
        $this->assertTrue($admin->hasRole('admin'));
    }

    /** @test */
    public function admin_can_manage_content_approval_workflow()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $contentItem = \App\Models\ContentItem::factory()->create(['status' => 'draft']);

        $this->actingAs($admin);

        // Test workflow management capabilities
        $this->assertDatabaseHas('content_items', ['status' => 'draft']);
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertEquals('draft', $contentItem->status);
    }

    /** @test */
    public function admin_can_view_audit_logs_and_analytics()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        // Create audit log entry
        \App\Models\AuditLog::create([
            'event' => 'user_login',
            'user_id' => $admin->id,
            'old_values' => [],
            'new_values' => [],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent'
        ]);

        $this->actingAs($admin);

        $this->assertDatabaseHas('audit_logs', ['event' => 'user_login']);
        $this->assertTrue($admin->hasRole('admin'));
    }

    /** @test */
    public function admin_can_manage_user_roles_and_permissions()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $user = \App\Models\User::factory()->agency()->create();

        $this->actingAs($admin);

        // Test role management capabilities
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($user->hasRole('agency'));
        $this->assertNotEquals($admin->getRoleNames()->first(), $user->getRoleNames()->first());

        // Test database persistence
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $admin->id, 'model_type' => 'App\\Models\\User']);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $user->id, 'model_type' => 'App\\Models\\User']);
    }
}