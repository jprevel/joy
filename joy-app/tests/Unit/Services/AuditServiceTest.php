<?php

namespace Tests\Unit\Services;

use App\Constants\AuditConstants;
use App\Models\AuditLog;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Test: AuditService
 * Tests audit logging and analysis logic
 */
class AuditServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_formats_audit_log_entries()
    {
        $log = AuditService::log('test_action');

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertEquals('test_action', $log->event);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'test_action'
        ]);
    }

    /** @test */
    public function it_analyzes_user_activity_patterns()
    {
        // Create activity for user
        $userId = 1;
        AuditLog::create(['event' => 'login', 'user_id' => $userId, 'severity' => 'info']);
        AuditLog::create(['event' => 'view_content', 'user_id' => $userId, 'severity' => 'info']);
        AuditLog::create(['event' => 'update_content', 'user_id' => $userId, 'severity' => 'info']);

        $activity = AuditLog::where('user_id', $userId)->get();

        $this->assertEquals(3, $activity->count());
        $this->assertTrue($activity->pluck('event')->contains('login'));
        $this->assertTrue($activity->pluck('event')->contains('view_content'));
    }

    /** @test */
    public function it_generates_security_reports()
    {
        // Create various log entries directly
        AuditLog::create(['event' => 'login', 'user_id' => 1, 'severity' => 'info']);
        AuditLog::create(['event' => 'failed_login', 'user_id' => 2, 'severity' => 'warning']);
        AuditLog::create(['event' => 'security_breach', 'user_id' => 3, 'severity' => 'error']);

        // Verify they were created
        $this->assertEquals(3, AuditLog::count());

        $report = AuditService::generateReport(null, 30);

        // Test that report has the expected structure
        $this->assertArrayHasKey('total_events', $report);
        $this->assertArrayHasKey('events_by_severity', $report);
        $this->assertArrayHasKey('errors', $report);

        // The report should show at least the logs we created
        $this->assertGreaterThanOrEqual(3, $report['total_events']);
    }

    /** @test */
    public function it_handles_log_cleanup_and_retention()
    {
        // Create old log
        $oldLog = new AuditLog([
            'event' => 'old_action',
            'severity' => AuditConstants::SEVERITY_INFO,
        ]);
        $oldLog->created_at = now()->subDays(100);
        $oldLog->save();

        // Create recent log
        AuditLog::create([
            'event' => 'recent_action',
            'severity' => AuditConstants::SEVERITY_INFO,
        ]);

        $countBefore = AuditLog::count();
        $this->assertEquals(2, $countBefore);

        $deleted = AuditService::cleanupOldLogs(90);

        $this->assertGreaterThanOrEqual(1, $deleted);
        $this->assertDatabaseHas('audit_logs', ['event' => 'recent_action']);
    }
}
