<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogViewerService;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogViewerServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogViewerService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable model observers for this test to avoid audit log side effects
        User::flushEventListeners();

        $this->service = new AuditLogViewerService();
    }

    /** @test */
    public function it_retrieves_recent_audit_logs_limited_to_100()
    {
        // Create 150 audit log entries
        AuditLog::factory()->count(150)->create();

        $result = $this->service->getRecentAuditLogs();

        $this->assertEquals(100, $result->count());
        $this->assertEquals('AuditLog', class_basename($result->first()));
    }

    /** @test */
    public function it_orders_audit_logs_by_created_at_desc()
    {
        $older = AuditLog::factory()->create(['created_at' => now()->subDays(2)]);
        $newer = AuditLog::factory()->create(['created_at' => now()->subDay()]);

        $result = $this->service->getRecentAuditLogs();

        $this->assertEquals($newer->id, $result->first()->id);
        $this->assertEquals($older->id, $result->last()->id);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $withinRange = AuditLog::factory()->create(['created_at' => now()->subDays(5)]);
        $outsideRange = AuditLog::factory()->create(['created_at' => now()->subDays(10)]);

        $filters = [
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d')
        ];

        $result = $this->service->getRecentAuditLogs($filters);

        $this->assertTrue($result->contains('id', $withinRange->id));
        $this->assertFalse($result->contains('id', $outsideRange->id));
    }

    /** @test */
    public function it_filters_by_user()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $log1 = AuditLog::factory()->create(['user_id' => $user1->id]);
        $log2 = AuditLog::factory()->create(['user_id' => $user2->id]);

        $filters = ['user_id' => $user1->id];

        $result = $this->service->getRecentAuditLogs($filters);

        $this->assertTrue($result->contains('id', $log1->id));
        $this->assertFalse($result->contains('id', $log2->id));
    }

    /** @test */
    public function it_filters_by_event_type()
    {
        $loginLog = AuditLog::factory()->create(['event' => 'user_login']);
        $logoutLog = AuditLog::factory()->create(['event' => 'user_logout']);

        $filters = ['event' => 'login'];

        $result = $this->service->getRecentAuditLogs($filters);

        $this->assertTrue($result->contains('id', $loginLog->id));
        $this->assertFalse($result->contains('id', $logoutLog->id));
    }

    /** @test */
    public function it_returns_empty_collection_when_no_logs_match_filters()
    {
        AuditLog::factory()->create(['event' => 'user_login']);

        $filters = ['event' => 'nonexistent_event'];

        $result = $this->service->getRecentAuditLogs($filters);

        $this->assertTrue($result->isEmpty());
    }

    /** @test */
    public function it_gets_distinct_event_types()
    {
        AuditLog::factory()->create(['event' => 'user_login']);
        AuditLog::factory()->create(['event' => 'user_login']); // duplicate
        AuditLog::factory()->create(['event' => 'content_created']);

        $eventTypes = $this->service->getEventTypes();

        $this->assertEquals(2, $eventTypes->count());
        $this->assertTrue($eventTypes->contains('user_login'));
        $this->assertTrue($eventTypes->contains('content_created'));
    }

    /** @test */
    public function it_gets_users_with_audit_log_entries()
    {
        $userWithLogs = User::factory()->create();
        $userWithoutLogs = User::factory()->create();

        AuditLog::factory()->create(['user_id' => $userWithLogs->id]);

        $users = $this->service->getAuditLogUsers();

        $this->assertEquals(1, $users->count());
        $this->assertEquals($userWithLogs->id, $users->first()->id);
    }

    /** @test */
    public function it_formats_audit_log_entry_correctly()
    {
        $user = User::factory()->create();
        $auditLog = AuditLog::factory()->create([
            'user_id' => $user->id,
            'event' => 'test_event',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'TestAgent',
            'old_values' => ['key' => 'old_value'],
            'new_values' => ['key' => 'new_value'],
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => 123,
        ]);

        $formatted = $this->service->formatAuditLogEntry($auditLog);

        $this->assertEquals($auditLog->id, $formatted['id']);
        $this->assertEquals('test_event', $formatted['event']);
        $this->assertEquals($user->name, $formatted['user']);
        $this->assertEquals($user->email, $formatted['user_email']);
        $this->assertEquals('127.0.0.1', $formatted['ip_address']);
        $this->assertEquals('TestAgent', $formatted['user_agent']);
        $this->assertEquals(['key' => 'old_value'], $formatted['old_values']);
        $this->assertEquals(['key' => 'new_value'], $formatted['new_values']);
        $this->assertEquals('App\\Models\\User', $formatted['auditable_type']);
        $this->assertEquals(123, $formatted['auditable_id']);
    }

    /** @test */
    public function it_handles_audit_log_without_user()
    {
        $auditLog = AuditLog::factory()->create([
            'user_id' => null,
            'event' => 'system_event'
        ]);

        $formatted = $this->service->formatAuditLogEntry($auditLog);

        $this->assertEquals('System', $formatted['user']);
        $this->assertNull($formatted['user_email']);
    }

    /** @test */
    public function it_gets_paginated_audit_logs()
    {
        AuditLog::factory()->count(30)->create();

        $result = $this->service->getPaginatedAuditLogs(10);

        $this->assertEquals(10, $result->count());
        $this->assertEquals(30, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }
}