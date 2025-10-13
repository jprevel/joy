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

    // ========== User Story 3: Improved Audit Access ==========

    /** @test */
    public function admin_dashboard_shows_single_view_logs_button()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.index'));

        $response->assertStatus(200);
        $response->assertSee('View Logs');
        // Should only appear once in the Audit card
        $content = $response->getContent();
        $this->assertEquals(1, substr_count($content, 'View Logs'));
    }

    /** @test */
    public function view_logs_button_navigates_to_audit_logs_page()
    {
        $this->actingAs($this->adminUser);

        // Create some audit logs to verify the page loads correctly
        AuditLog::factory()->count(5)->create();

        $response = $this->get(route('admin.audit.recent'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.audit.recent');
        $response->assertSee('Recent Audit Logs');
    }

    /** @test */
    public function dashboard_does_not_show_separate_recent_logs_button()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.index'));

        $response->assertStatus(200);
        // Should not have the old button texts in the Audit card
        $content = $response->getContent();
        // Check that "Recent Logs" button text doesn't exist
        $this->assertStringNotContainsString('Recent Logs', $content);
        // Check that we don't have the route to audit.dashboard
        $this->assertStringNotContainsString(route('admin.audit.dashboard'), $content);
    }

    // ========== User Story 4: Collapsible Audit Filters ==========

    /** @test */
    public function audit_filters_are_collapsed_by_default()
    {
        $this->actingAs($this->adminUser);

        \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class)
            ->assertSet('filtersOpen', false)
            ->assertSee('Show Filters')
            ->assertDontSee('Hide Filters');
    }

    /** @test */
    public function filter_button_toggles_filter_form_visibility()
    {
        $this->actingAs($this->adminUser);

        \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class)
            ->assertSet('filtersOpen', false)
            ->call('toggleFilters')
            ->assertSet('filtersOpen', true)
            ->call('toggleFilters')
            ->assertSet('filtersOpen', false);
    }

    /** @test */
    public function active_filters_show_indicator_when_collapsed()
    {
        $this->actingAs($this->adminUser);

        // Create some audit logs and users
        AuditLog::factory()->count(5)->create();

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class)
            ->set('search', 'test')
            ->set('eventFilter', 'user_login')
            ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
            ->set('filtersOpen', false);

        // Should show active filter count
        // Note: dateFrom, dateTo are set by default in mount(), plus search and eventFilter = 4 total
        $this->assertEquals(4, $component->get('activeFilterCount'));
    }

    /** @test */
    public function filter_form_is_compact_three_column_layout()
    {
        $this->actingAs($this->adminUser);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class)
            ->set('filtersOpen', true);

        $html = $component->html();

        // Check that the view contains grid-cols-3 for compact layout
        $this->assertStringContainsString('grid-cols-3', $html);
        // Should not have the old 5-column layout
        $this->assertStringNotContainsString('grid-cols-5', $html);
    }

    // ========== User Story 5: Enhanced Audit Log Details ==========

    /** @test */
    public function audit_logs_display_inline_change_details()
    {
        $this->actingAs($this->adminUser);

        // Create audit log with change details
        $auditLog = AuditLog::factory()->create([
            'event' => 'user_updated',
            'user_id' => $this->adminUser->id,
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => 1,
            'old_values' => ['name' => 'Old Name', 'email' => 'old@example.com'],
            'new_values' => ['name' => 'New Name', 'email' => 'new@example.com']
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class);
        $html = $component->html();

        // Should show field names and values in "old → new" format
        $this->assertMatchesRegularExpression('/[Nn]ame/', $html);
        $this->assertStringContainsString('Old Name', $html);
        $this->assertStringContainsString('New Name', $html);
        $this->assertMatchesRegularExpression('/[Ee]mail/', $html);
        $this->assertStringContainsString('old@example.com', $html);
        $this->assertStringContainsString('new@example.com', $html);
    }

    /** @test */
    public function audit_logs_do_not_show_ip_address_column()
    {
        $this->actingAs($this->adminUser);

        AuditLog::factory()->create([
            'event' => 'test_event',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.100'
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class);
        $html = $component->html();

        // IP address should NOT appear in the table
        $this->assertStringNotContainsString('192.168.1.100', $html);
        // Should not have IP Address column header
        $this->assertStringNotContainsString('IP Address', $html);
    }

    /** @test */
    public function large_change_sets_truncate_with_expand_toggle()
    {
        $this->actingAs($this->adminUser);

        // Create audit log with many changes (>5 fields)
        $oldValues = [];
        $newValues = [];
        for ($i = 1; $i <= 10; $i++) {
            $oldValues["field_{$i}"] = "old_value_{$i}";
            $newValues["field_{$i}"] = "new_value_{$i}";
        }

        $auditLog = AuditLog::factory()->create([
            'event' => 'user_updated',
            'user_id' => $this->adminUser->id,
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => 1,
            'old_values' => $oldValues,
            'new_values' => $newValues
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class);
        $html = $component->html();

        // Should show truncation indicator
        $this->assertMatchesRegularExpression('/Show (all )?(\d+) (more )?changes?/i', $html);
    }

    /** @test */
    public function change_details_are_human_readable()
    {
        $this->actingAs($this->adminUser);

        // Create audit log with underscored field names
        $auditLog = AuditLog::factory()->create([
            'event' => 'user_updated',
            'user_id' => $this->adminUser->id,
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => 1,
            'old_values' => ['first_name' => 'John', 'last_login_at' => '2024-01-01'],
            'new_values' => ['first_name' => 'Jane', 'last_login_at' => '2024-01-15']
        ]);

        $component = \Livewire\Livewire::test(\App\Livewire\Admin\AuditLogs::class);
        $html = $component->html();

        // Field names should be human-readable (spaces instead of underscores, capitalized)
        $this->assertMatchesRegularExpression('/First [Nn]ame/i', $html);
        $this->assertMatchesRegularExpression('/Last [Ll]ogin/i', $html);
    }
}