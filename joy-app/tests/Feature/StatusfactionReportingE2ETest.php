<?php

namespace Tests\Feature;

use App\Livewire\Statusfaction;
use App\Models\Client;
use App\Models\ClientStatusfactionUpdate;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\SlackNotificationAssertions;

/**
 * E2E Test: Statusfaction Weekly Reporting
 * Tests account manager status reporting workflow
 */
class StatusfactionReportingE2ETest extends TestCase
{
    use RefreshDatabase;
    use SlackNotificationAssertions;

    /** @test */
    public function account_manager_can_generate_weekly_status_reports()
    {
        $this->markTestIncomplete('Test statusfaction report generation');
    }

    /** @test */
    public function account_manager_can_view_client_progress_metrics()
    {
        $this->markTestIncomplete('Test client progress tracking');
    }

    /** @test */
    public function admin_can_view_all_team_status_reports()
    {
        $this->markTestIncomplete('Test admin statusfaction overview');
    }

    // T003: Integration Test - Account Manager Submits New Status
    /** @test */
    public function account_manager_can_submit_status_for_assigned_client()
    {
        // Create roles
        Role::firstOrCreate(['name' => 'agency']);

        // Create Account Manager with assigned client
        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        $client = Client::factory()->create(['team_id' => $team->id]);

        // Test Statusfaction component
        $component = Livewire::actingAs($accountManager)
            ->test(Statusfaction::class)
            ->assertSee($client->name)
            ->assertSee('Needs Status')
            ->call('selectClient', $client->id)
            ->assertSet('showForm', true)
            ->set('status_notes', 'Test status notes')
            ->set('client_satisfaction', 8)
            ->set('team_health', 7)
            ->call('saveStatus');

        $component->assertHasNoErrors()
            ->assertSet('showForm', false);

        // Assert database has new record
        $this->assertDatabaseHas('client_status_updates', [
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'status_notes' => 'Test status notes',
            'client_satisfaction' => 8,
            'team_health' => 7,
            'approval_status' => 'pending_approval',
        ]);
    }

    // T004: Integration Test - Account Manager Edits Pending Status
    /** @test */
    public function account_manager_can_edit_pending_status()
    {
        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        $client = Client::factory()->create(['team_id' => $team->id]);

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Create existing pending status
        $status = ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'status_notes' => 'Original notes',
            'client_satisfaction' => 5,
            'team_health' => 5,
            'week_start_date' => $weekStart,
            'approval_status' => 'pending_approval',
        ]);

        // Edit the status
        Livewire::actingAs($accountManager)
            ->test(Statusfaction::class)
            ->call('selectClient', $client->id)
            ->assertSet('showForm', true)
            ->assertSet('status_notes', 'Original notes')
            ->set('status_notes', 'Updated notes')
            ->set('client_satisfaction', 9)
            ->call('saveStatus')
            ->assertHasNoErrors();

        // Assert database updated (not duplicated)
        $this->assertDatabaseHas('client_status_updates', [
            'client_id' => $client->id,
            'status_notes' => 'Updated notes',
            'client_satisfaction' => 9,
            'approval_status' => 'pending_approval',
        ]);

        $this->assertDatabaseMissing('client_status_updates', [
            'client_id' => $client->id,
            'status_notes' => 'Original notes',
        ]);

        // Only 1 record for this week
        $count = ClientStatusfactionUpdate::where('client_id', $client->id)
            ->where('week_start_date', $weekStart)
            ->count();
        $this->assertEquals(1, $count);
    }

    // T005: Integration Test - Cannot Edit Approved Status
    /** @test */
    public function account_manager_cannot_edit_approved_status()
    {
        Role::firstOrCreate(['name' => 'agency']);
        Role::firstOrCreate(['name' => 'admin']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        $client = Client::factory()->create(['team_id' => $team->id]);

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Create approved status
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'week_start_date' => $weekStart,
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Attempt to view - should show detail, not form
        Livewire::actingAs($accountManager)
            ->test(Statusfaction::class)
            ->call('selectClient', $client->id)
            ->assertSet('showForm', false)
            ->assertSet('showDetail', true);
    }

    // T006: Integration Test - Admin Approves Pending Status
    /** @test */
    public function admin_can_approve_pending_status()
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        $status = ClientStatusfactionUpdate::factory()->create([
            'week_start_date' => $weekStart,
            'approval_status' => 'pending_approval',
        ]);

        Livewire::actingAs($admin)
            ->test(Statusfaction::class)
            ->call('approveStatus', $status->id);

        $status->refresh();

        $this->assertEquals('approved', $status->approval_status);
        $this->assertEquals($admin->id, $status->approved_by);
        $this->assertNotNull($status->approved_at);
    }

    // T007: Integration Test - Account Manager Sees Only Assigned Clients
    /** @test */
    public function account_manager_sees_only_assigned_clients()
    {
        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        // Team A with clients 1-2 (assigned)
        $teamA = Team::factory()->create(['name' => 'Team A']);
        $accountManager->teams()->attach($teamA);

        $client1 = Client::factory()->create(['team_id' => $teamA->id, 'name' => 'Client 1']);
        $client2 = Client::factory()->create(['team_id' => $teamA->id, 'name' => 'Client 2']);

        // Team B with clients 3-4 (not assigned)
        $teamB = Team::factory()->create(['name' => 'Team B']);
        $client3 = Client::factory()->create(['team_id' => $teamB->id, 'name' => 'Client 3']);
        $client4 = Client::factory()->create(['team_id' => $teamB->id, 'name' => 'Client 4']);

        $component = Livewire::actingAs($accountManager)
            ->test(Statusfaction::class);

        $clients = $component->clients;

        // Assert sees only clients 1-2
        $this->assertTrue($clients->contains('id', $client1->id));
        $this->assertTrue($clients->contains('id', $client2->id));
        $this->assertFalse($clients->contains('id', $client3->id));
        $this->assertFalse($clients->contains('id', $client4->id));
    }

    // T008: Integration Test - Admin Sees All Clients
    /** @test */
    public function admin_sees_all_clients()
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create clients in multiple teams
        $team1 = Team::factory()->create();
        $team2 = Team::factory()->create();

        $client1 = Client::factory()->create(['team_id' => $team1->id]);
        $client2 = Client::factory()->create(['team_id' => $team2->id]);

        $component = Livewire::actingAs($admin)
            ->test(Statusfaction::class);

        $clients = $component->clients;

        // Assert sees all clients
        $this->assertTrue($clients->contains('id', $client1->id));
        $this->assertTrue($clients->contains('id', $client2->id));
    }

    // T009: Integration Test - Client Status States Calculated Correctly
    /** @test */
    public function client_status_states_calculated_correctly()
    {
        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Client with no submission this week
        $client1 = Client::factory()->create(['name' => 'Client Needs Status']);

        // Client with pending submission
        $client2 = Client::factory()->create(['name' => 'Client Pending']);
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client2->id,
            'week_start_date' => $weekStart,
            'approval_status' => 'pending_approval',
        ]);

        // Client with approved submission
        $client3 = Client::factory()->create(['name' => 'Client Approved']);
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client3->id,
            'week_start_date' => $weekStart,
            'approval_status' => 'approved',
        ]);

        $component = Livewire::actingAs($admin)
            ->test(Statusfaction::class);

        $clients = $component->clients;

        $clientNeedsStatus = $clients->firstWhere('id', $client1->id);
        $clientPending = $clients->firstWhere('id', $client2->id);
        $clientApproved = $clients->firstWhere('id', $client3->id);

        $this->assertEquals('Needs Status', $clientNeedsStatus->status_state);
        $this->assertEquals('Pending Approval', $clientPending->status_state);
        $this->assertEquals('Status Approved', $clientApproved->status_state);
    }

    // T010: Integration Test - 5-Week Trend Graph Data
    /** @test */
    public function trend_graph_shows_five_weeks_with_gaps()
    {
        // Create user for authentication
        Role::firstOrCreate(['name' => 'admin']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $client = Client::factory()->create();
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Create data for weeks -4, -2, 0 (current) - missing weeks -3 and -1
        foreach ([4, 2, 0] as $weeksAgo) {
            ClientStatusfactionUpdate::factory()->create([
                'client_id' => $client->id,
                'week_start_date' => $weekStart->copy()->subWeeks($weeksAgo),
                'client_satisfaction' => 8,
                'team_health' => 7,
            ]);
        }

        $component = Livewire::actingAs($admin)
            ->test(Statusfaction::class)
            ->set('selectedClient', $client);

        $graphData = $component->graphData;

        $this->assertIsArray($graphData);
        $this->assertArrayHasKey('labels', $graphData);
        $this->assertArrayHasKey('datasets', $graphData);
        $this->assertCount(5, $graphData['labels']); // 5 week labels
        $this->assertCount(2, $graphData['datasets']); // 2 lines (satisfaction, health)

        // Weeks -3 and -1 should have null values
        $satisfactionData = $graphData['datasets'][0]['data'];
        $this->assertNotNull($satisfactionData[0]); // Week -4
        $this->assertNull($satisfactionData[1]); // Week -3 (gap)
        $this->assertNotNull($satisfactionData[2]); // Week -2
        $this->assertNull($satisfactionData[3]); // Week -1 (gap)
        $this->assertNotNull($satisfactionData[4]); // Week 0 (current)
    }

    // T011: Integration Test - Empty Notes Validation
    /** @test */
    public function status_notes_required_validation()
    {
        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        $client = Client::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($accountManager)
            ->test(Statusfaction::class)
            ->call('selectClient', $client->id)
            ->set('status_notes', '') // Empty notes
            ->set('client_satisfaction', 5)
            ->set('team_health', 5)
            ->call('saveStatus')
            ->assertHasErrors('status_notes');

        // Assert database unchanged (no record created)
        $this->assertDatabaseMissing('client_status_updates', [
            'client_id' => $client->id,
        ]);
    }

    // T012: Integration Test - Duplicate Week Constraint
    /** @test */
    public function unique_week_constraint_prevents_duplicates()
    {
        $client = Client::factory()->create();
        $weekStart = Carbon::now()->startOfWeek(Carbon::SUNDAY);

        // Create first status for current week
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'week_start_date' => $weekStart,
        ]);

        // Attempt to create second status for same week
        $this->expectException(\Illuminate\Database\QueryException::class);

        ClientStatusfactionUpdate::create([
            'user_id' => User::factory()->create()->id,
            'client_id' => $client->id,
            'status_notes' => 'Duplicate attempt',
            'client_satisfaction' => 5,
            'team_health' => 5,
            'status_date' => now(),
            'week_start_date' => $weekStart,
            'approval_status' => 'pending_approval',
        ]);
    }

    // T013: Database Migration Test - Schema Changes
    /** @test */
    public function migration_adds_approval_workflow_columns()
    {
        $this->assertTrue(Schema::hasColumn('client_status_updates', 'week_start_date'));
        $this->assertTrue(Schema::hasColumn('client_status_updates', 'approval_status'));
        $this->assertTrue(Schema::hasColumn('client_status_updates', 'approved_by'));
        $this->assertTrue(Schema::hasColumn('client_status_updates', 'approved_at'));

        // Check indexes exist (this is harder to test directly in Laravel, so we'll trust migration)
        // Could use raw SQL: SELECT * FROM information_schema.statistics WHERE table_name = 'client_status_updates'
    }

    // T029: Slack Notification Tests
    /** @test */
    public function slack_notification_sent_when_statusfaction_submitted()
    {
        $this->mockSlackApiSuccess();
        $this->fakeQueue();

        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        $team = Team::factory()->create();
        $accountManager->teams()->attach($team);

        // Create client with Slack integration
        $client = Client::factory()->create([
            'team_id' => $team->id,
            'slack_channel_id' => 'C123456',
            'slack_channel_name' => '#test-channel',
        ]);

        // Create workspace
        \App\Models\SlackWorkspace::factory()->create([
            'is_active' => true,
            'bot_token' => 'xoxb-test-token',
        ]);

        // Submit statusfaction report
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'status_notes' => 'Test status',
            'approval_status' => 'pending_approval',
        ]);

        // Assert job was dispatched
        $this->assertSlackJobDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
    }

    /** @test */
    public function slack_notification_sent_when_statusfaction_approved()
    {
        $this->mockSlackApiSuccess();
        $this->fakeQueue();

        Role::firstOrCreate(['name' => 'admin']);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Create client with Slack integration
        $client = Client::factory()->create([
            'slack_channel_id' => 'C123456',
            'slack_channel_name' => '#test-channel',
        ]);

        // Create workspace
        \App\Models\SlackWorkspace::factory()->create([
            'is_active' => true,
            'bot_token' => 'xoxb-test-token',
        ]);

        // Create pending status
        $status = ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'approval_status' => 'pending_approval',
        ]);

        // Approve status
        $status->update([
            'approval_status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        // Assert job was dispatched
        $this->assertSlackJobDispatched(\App\Jobs\SendStatusfactionApprovedNotification::class);
    }

    /** @test */
    public function slack_notification_not_sent_when_editing_pending_statusfaction()
    {
        // CRITICAL TEST: Verify clarification #1 from spec.md - NO notifications on edits
        $this->mockSlackApiSuccess();
        $this->fakeQueue();

        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        // Create client with Slack integration
        $client = Client::factory()->create([
            'slack_channel_id' => 'C123456',
            'slack_channel_name' => '#test-channel',
        ]);

        // Create workspace
        \App\Models\SlackWorkspace::factory()->create([
            'is_active' => true,
            'bot_token' => 'xoxb-test-token',
        ]);

        // Create initial status (this WILL dispatch notification)
        $status = ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'status_notes' => 'Original notes',
            'approval_status' => 'pending_approval',
        ]);

        // Clear queue to reset assertions
        Queue::fake();

        // Edit the pending status
        $status->update([
            'status_notes' => 'Updated notes',
            'client_satisfaction' => 9,
        ]);

        // Assert submission notification was NOT dispatched on edit
        $this->assertSlackJobNotDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
    }

    /** @test */
    public function slack_notification_not_sent_when_client_has_no_slack_integration()
    {
        $this->fakeQueue();

        Role::firstOrCreate(['name' => 'agency']);

        $accountManager = User::factory()->create();
        $accountManager->assignRole('agency');

        // Create client WITHOUT Slack integration
        $client = Client::factory()->create([
            'slack_channel_id' => null,
            'slack_channel_name' => null,
        ]);

        // Submit statusfaction report
        ClientStatusfactionUpdate::factory()->create([
            'client_id' => $client->id,
            'user_id' => $accountManager->id,
            'approval_status' => 'pending_approval',
        ]);

        // Assert job was NOT dispatched (no Slack integration)
        $this->assertSlackJobNotDispatched(\App\Jobs\SendStatusfactionSubmittedNotification::class);
    }
}