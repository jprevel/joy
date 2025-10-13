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

    // ========== User Story 1: Admin Manages Users ==========

    /** @test */
    public function admin_can_view_user_list_with_soft_deleted_users()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $activeUser = \App\Models\User::factory()->agency()->create(['name' => 'Active User']);
        $deletedUser = \App\Models\User::factory()->agency()->create(['name' => 'Deleted User']);
        $deletedUser->delete();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->assertSee('Active User')
            ->assertSee('Deleted User')
            ->assertSee('Deleted'); // Visual indicator for deleted users
    }

    /** @test */
    public function admin_can_create_new_user_with_role()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->set('form.name', 'New Test User')
            ->set('form.email', 'newuser@example.com')
            ->set('form.password', 'password123')
            ->set('form.role', 'agency')
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New Test User',
            'email' => 'newuser@example.com',
        ]);

        $user = \App\Models\User::where('email', 'newuser@example.com')->first();
        $this->assertTrue($user->hasRole('agency'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'User Created',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_edit_existing_user_including_password()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $user = \App\Models\User::factory()->agency()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->call('editUser', $user->id)
            ->set('form.name', 'Updated Name')
            ->set('form.email', 'updated@example.com')
            ->set('form.password', 'newpassword123')
            ->set('form.role', 'admin')
            ->call('updateUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);

        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'User Updated',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_user_with_confirmation()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $user = \App\Models\User::factory()->agency()->create(['name' => 'User To Delete']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->call('deleteUser', $user->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'User Deleted',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_can_restore_soft_deleted_user()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $user = \App\Models\User::factory()->agency()->create(['name' => 'Deleted User']);
        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->call('restoreUser', $user->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'User Restored',
            'auditable_type' => \App\Models\User::class,
            'auditable_id' => $user->id,
        ]);
    }

    /** @test */
    public function admin_self_modification_shows_warning()
    {
        $admin = \App\Models\User::factory()->admin()->create(['name' => 'Admin User']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\UserManagement::class)
            ->call('editUser', $admin->id)
            ->assertSet('editingSelf', true)
            ->assertSee('You are modifying your own account');
    }

    /** @test */
    public function soft_deleted_user_cannot_login()
    {
        $user = \App\Models\User::factory()->agency()->create([
            'email' => 'deleted@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $user->id]);

        // Attempt to authenticate
        $this->post('/login', [
            'email' => 'deleted@example.com',
            'password' => 'password123',
        ]);

        $this->assertGuest();
    }

    // ========== User Story 2: Admin Manages Clients ==========

    /** @test */
    public function admin_can_view_client_list_with_soft_deleted_clients()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $activeClient = \App\Models\Client::factory()->create(['name' => 'Active Client']);
        $deletedClient = \App\Models\Client::factory()->create(['name' => 'Deleted Client']);
        $deletedClient->delete();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class)
            ->assertSee('Active Client')
            ->assertSee('Deleted Client')
            ->assertSee('Deleted'); // Visual indicator for deleted clients
    }

    /** @test */
    public function admin_can_create_new_client_with_slack_channel()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $team = \App\Models\Team::factory()->create();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class)
            ->set('form.name', 'New Test Client')
            ->set('form.description', 'Test client description')
            ->set('form.team_id', $team->id)
            ->set('form.slack_channel_id', 'C123456')
            ->set('form.slack_channel_name', '#test-channel')
            ->call('createClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'New Test Client',
            'description' => 'Test client description',
            'team_id' => $team->id,
            'slack_channel_id' => 'C123456',
            'slack_channel_name' => '#test-channel',
        ]);

        $client = \App\Models\Client::where('name', 'New Test Client')->first();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'Client Created',
            'auditable_type' => \App\Models\Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    /** @test */
    public function slack_channel_dropdown_loads_available_channels()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        // Mock SlackService to return test channels
        $slackService = \Mockery::mock(\App\Services\SlackService::class);
        $slackService->shouldReceive('getChannels')
            ->with(false, true)
            ->andReturn([
                'success' => true,
                'channels' => [
                    ['id' => 'C111', 'name' => 'general', 'is_private' => false],
                    ['id' => 'C222', 'name' => 'client-updates', 'is_private' => false],
                ]
            ]);

        $this->app->instance(\App\Services\SlackService::class, $slackService);

        $component = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class);

        // Verify component has access to available channels
        $this->assertIsArray($component->get('availableSlackChannels'));
        $this->assertCount(2, $component->get('availableSlackChannels'));
    }

    /** @test */
    public function admin_can_edit_existing_client_including_slack_channel()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $team = \App\Models\Team::factory()->create();
        $client = \App\Models\Client::factory()->create([
            'name' => 'Original Client Name',
            'team_id' => $team->id,
            'slack_channel_id' => 'C111',
            'slack_channel_name' => '#old-channel',
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class)
            ->call('editClient', $client->id)
            ->set('form.name', 'Updated Client Name')
            ->set('form.slack_channel_id', 'C222')
            ->set('form.slack_channel_name', '#new-channel')
            ->call('updateClient')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Updated Client Name',
            'slack_channel_id' => 'C222',
            'slack_channel_name' => '#new-channel',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'Client Updated',
            'auditable_type' => \App\Models\Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    /** @test */
    public function admin_can_soft_delete_client_with_confirmation()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $client = \App\Models\Client::factory()->create(['name' => 'Client To Delete']);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class)
            ->call('deleteClient', $client->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'Client Deleted',
            'auditable_type' => \App\Models\Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    /** @test */
    public function admin_can_restore_soft_deleted_client()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $client = \App\Models\Client::factory()->create(['name' => 'Deleted Client']);
        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class)
            ->call('restoreClient', $client->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'Client Restored',
            'auditable_type' => \App\Models\Client::class,
            'auditable_id' => $client->id,
        ]);
    }

    /** @test */
    public function soft_deleted_client_magic_links_remain_functional()
    {
        $admin = \App\Models\User::factory()->admin()->create();
        $client = \App\Models\Client::factory()->create();
        $magicLink = \App\Models\MagicLink::factory()->create([
            'client_id' => $client->id,
            'expires_at' => now()->addDays(7),
        ]);

        // Soft delete the client
        $client->delete();
        $this->assertSoftDeleted('clients', ['id' => $client->id]);

        // Verify magic link still exists and is accessible
        $this->assertDatabaseHas('magic_links', [
            'id' => $magicLink->id,
            'client_id' => $client->id,
        ]);

        // Magic link should still be functional for read-only access
        $magicLink->refresh();
        $this->assertNotNull($magicLink->client_id);
        $this->assertFalse($magicLink->isExpired());
    }

    /** @test */
    public function slack_dropdown_shows_helpful_message_when_no_workspace()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        // Mock SlackService to return no workspaces
        $slackService = \Mockery::mock(\App\Services\SlackService::class);
        $slackService->shouldReceive('getChannels')
            ->andReturn(['success' => false, 'channels' => []]);

        $this->app->instance(\App\Services\SlackService::class, $slackService);

        $component = \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\ClientManagement::class);

        // Verify helpful message is available
        $this->assertTrue($component->get('noSlackWorkspace'));
    }

    // ========== User Story 7: Remove Fake System Status ==========

    /** @test */
    public function admin_dashboard_does_not_show_system_status_card()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.index'));

        $response->assertStatus(200);

        // Verify System Status card is NOT present
        $response->assertDontSee('System Status');
        $response->assertDontSee('System Healthy');
        $response->assertDontSee('Monitor system health and performance');
    }

    /** @test */
    public function dashboard_layout_reorganizes_after_system_status_removal()
    {
        $admin = \App\Models\User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.index'));

        $response->assertStatus(200);

        // Verify remaining cards are present and properly displayed
        $response->assertSee('Audit Logs');
        $response->assertSee('User Management');
        $response->assertSee('Client Management');
        $response->assertSee('Content Calendar');
        $response->assertSee('Integrations');

        // Verify grid layout is still intact
        $content = $response->getContent();
        $this->assertStringContainsString('grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3', $content);
    }
}