<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogViewerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
    }

    /** @test */
    public function admin_can_access_recent_audit_logs_page()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.audit.recent'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit.recent');
        $response->assertSee('Recent Audit Logs');
        $response->assertSee('Last 100 entries');
    }

    /** @test */
    public function recent_audit_logs_page_displays_audit_logs()
    {
        $this->actingAs($this->adminUser);

        $auditLog = AuditLog::factory()->create([
            'event' => 'test_event',
            'user_id' => $this->adminUser->id,
            'ip_address' => '127.0.0.1'
        ]);

        $response = $this->get(route('admin.audit.recent'));

        $response->assertStatus(200);
        $response->assertSee('test_event');
        $response->assertSee($this->adminUser->name);
        $response->assertSee('127.0.0.1');
    }

    /** @test */
    public function recent_audit_logs_api_returns_json_data()
    {
        $this->actingAs($this->adminUser);

        $auditLog = AuditLog::factory()->create([
            'event' => 'api_test_event',
            'user_id' => $this->adminUser->id
        ]);

        $response = $this->getJson(route('admin.audit.recent'));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true
        ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(1, $response->json('count'));

        // Find the api_test_event in the results
        $testEvent = collect($data)->firstWhere('event', 'api_test_event');
        $this->assertNotNull($testEvent);
        $this->assertEquals($this->adminUser->name, $testEvent['user']);
    }

    /** @test */
    public function recent_audit_logs_limits_to_100_entries()
    {
        $this->actingAs($this->adminUser);

        // Create 150 audit log entries
        AuditLog::factory()->count(150)->create();

        $response = $this->getJson(route('admin.audit.recent'));

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(100, $data);
    }

    /** @test */
    public function recent_audit_logs_can_be_filtered_by_date()
    {
        $this->actingAs($this->adminUser);

        $oldLog = AuditLog::factory()->create([
            'event' => 'old_event',
            'created_at' => now()->subDays(10)
        ]);

        $recentLog = AuditLog::factory()->create([
            'event' => 'recent_event',
            'created_at' => now()->subDays(2)
        ]);

        $response = $this->getJson(route('admin.audit.recent', [
            'date_from' => now()->subDays(5)->format('Y-m-d')
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should include recent_event but not old_event
        $events = collect($data)->pluck('event')->toArray();
        $this->assertContains('recent_event', $events);
        $this->assertNotContains('old_event', $events);
    }

    /** @test */
    public function recent_audit_logs_can_be_filtered_by_user()
    {
        $this->actingAs($this->adminUser);

        $otherUser = User::factory()->create();

        $adminLog = AuditLog::factory()->create([
            'event' => 'admin_event',
            'user_id' => $this->adminUser->id
        ]);

        $otherLog = AuditLog::factory()->create([
            'event' => 'other_event',
            'user_id' => $otherUser->id
        ]);

        $response = $this->getJson(route('admin.audit.recent', [
            'user_id' => $this->adminUser->id
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should include admin_event but not other_event
        $events = collect($data)->pluck('event')->toArray();
        $this->assertContains('admin_event', $events);
        $this->assertNotContains('other_event', $events);

        // All events should be from the admin user
        foreach ($data as $log) {
            $this->assertEquals($this->adminUser->name, $log['user']);
        }
    }

    /** @test */
    public function recent_audit_logs_can_be_filtered_by_event_type()
    {
        $this->actingAs($this->adminUser);

        $loginLog = AuditLog::factory()->create(['event' => 'user_login']);
        $createLog = AuditLog::factory()->create(['event' => 'content_created']);

        $response = $this->getJson(route('admin.audit.recent', [
            'event' => 'login'
        ]));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertEquals('user_login', $data[0]['event']);
    }

    /** @test */
    public function recent_audit_logs_shows_empty_state_message_when_no_logs()
    {
        $this->actingAs($this->adminUser);

        // Filter by a non-existent event type to get no results
        $response = $this->getJson(route('admin.audit.recent', [
            'event' => 'non_existent_event_type_xyz'
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 0,
            'message' => 'No audit log entries found matching the criteria.'
        ]);
    }

    /** @test */
    public function recent_audit_logs_shows_empty_state_when_filtered_results_empty()
    {
        $this->actingAs($this->adminUser);

        AuditLog::factory()->create(['event' => 'user_login']);

        $response = $this->getJson(route('admin.audit.recent', [
            'event' => 'nonexistent'
        ]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'count' => 0,
            'message' => 'No audit log entries found matching the criteria.'
        ]);
    }

    /** @test */
    public function recent_audit_logs_page_shows_filter_options()
    {
        $this->actingAs($this->adminUser);

        $user = User::factory()->create();
        AuditLog::factory()->create([
            'event' => 'test_event',
            'user_id' => $user->id
        ]);

        $response = $this->get(route('admin.audit.recent'));

        $response->assertStatus(200);
        $response->assertSee($user->name);
        $response->assertSee('test_event');
        $response->assertSee('Apply Filters');
    }

    /** @test */
    public function recent_audit_logs_page_handles_applied_filters()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.audit.recent', [
            'event' => 'test_filter'
        ]));

        $response->assertStatus(200);
        $response->assertSee('Clear all filters');
    }
}