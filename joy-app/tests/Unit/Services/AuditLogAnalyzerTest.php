<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogAnalyzer;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditLogAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->analyzer = new AuditLogAnalyzer();
    }

    /** @test */
    public function it_detects_audit_changes_when_old_values_present()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['title' => 'Old Title'],
            'new_values' => [],
        ]);
        
        // Act
        $hasChanges = $this->analyzer->hasAuditChanges($auditLog);
        
        // Assert
        $this->assertTrue($hasChanges);
    }

    /** @test */
    public function it_detects_audit_changes_when_new_values_present()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => [],
            'new_values' => ['title' => 'New Title'],
        ]);
        
        // Act
        $hasChanges = $this->analyzer->hasAuditChanges($auditLog);
        
        // Assert
        $this->assertTrue($hasChanges);
    }

    /** @test */
    public function it_detects_no_changes_when_both_values_empty()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => [],
            'new_values' => [],
        ]);
        
        // Act
        $hasChanges = $this->analyzer->hasAuditChanges($auditLog);
        
        // Assert
        $this->assertFalse($hasChanges);
    }

    /** @test */
    public function it_gets_changed_fields_from_both_old_and_new_values()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['title' => 'Old Title', 'status' => 'draft'],
            'new_values' => ['title' => 'New Title', 'description' => 'New Description'],
        ]);
        
        // Act
        $changedFields = $this->analyzer->getChangedFields($auditLog);
        
        // Assert
        $this->assertCount(3, $changedFields);
        $this->assertContains('title', $changedFields);
        $this->assertContains('status', $changedFields);
        $this->assertContains('description', $changedFields);
    }

    /** @test */
    public function it_returns_empty_array_when_no_changes()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => [],
            'new_values' => [],
        ]);
        
        // Act
        $changedFields = $this->analyzer->getChangedFields($auditLog);
        
        // Assert
        $this->assertEmpty($changedFields);
    }

    /** @test */
    public function it_analyzes_field_changes_in_detail()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => [
                'title' => 'Old Title',      // Modified
                'status' => 'draft',         // Removed
                'existing' => 'value',       // No change (shouldn't appear in new_values)
            ],
            'new_values' => [
                'title' => 'New Title',      // Modified
                'description' => 'New Desc', // Added
                'existing' => 'value',       // No change
            ],
        ]);
        
        // Act
        $changes = $this->analyzer->getFieldChanges($auditLog);
        
        // Assert
        $this->assertArrayHasKey('title', $changes);
        $this->assertArrayHasKey('status', $changes);
        $this->assertArrayHasKey('description', $changes);
        $this->assertArrayHasKey('existing', $changes);
        
        // Test modified field
        $this->assertTrue($changes['title']['changed']);
        $this->assertTrue($changes['title']['modified']);
        $this->assertFalse($changes['title']['added']);
        $this->assertFalse($changes['title']['removed']);
        $this->assertEquals('Old Title', $changes['title']['old_value']);
        $this->assertEquals('New Title', $changes['title']['new_value']);
        
        // Test removed field
        $this->assertTrue($changes['status']['changed']);
        $this->assertTrue($changes['status']['removed']);
        $this->assertFalse($changes['status']['added']);
        $this->assertFalse($changes['status']['modified']);
        $this->assertEquals('draft', $changes['status']['old_value']);
        $this->assertNull($changes['status']['new_value']);
        
        // Test added field
        $this->assertTrue($changes['description']['changed']);
        $this->assertTrue($changes['description']['added']);
        $this->assertFalse($changes['description']['removed']);
        $this->assertFalse($changes['description']['modified']);
        $this->assertNull($changes['description']['old_value']);
        $this->assertEquals('New Desc', $changes['description']['new_value']);
        
        // Test unchanged field
        $this->assertFalse($changes['existing']['changed']);
        $this->assertFalse($changes['existing']['added']);
        $this->assertFalse($changes['existing']['removed']);
        $this->assertFalse($changes['existing']['modified']);
        $this->assertEquals('value', $changes['existing']['old_value']);
        $this->assertEquals('value', $changes['existing']['new_value']);
    }

    /** @test */
    public function it_gets_statistics_for_given_period()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create test data
        AuditLog::factory()->count(3)->create([
            'action' => 'created',
            'severity' => 'info',
            'user_type' => 'admin',
            'created_at' => $now->copy()->subDays(5),
        ]);
        
        AuditLog::factory()->count(2)->create([
            'action' => 'updated',
            'severity' => 'warning',
            'user_type' => 'client',
            'created_at' => $now->copy()->subDays(10),
        ]);
        
        AuditLog::factory()->create([
            'action' => 'deleted',
            'severity' => 'error',
            'user_type' => 'agency',
            'created_at' => $now->copy()->subDays(40), // Outside 30 day window
        ]);
        
        // Act
        $stats = $this->analyzer->getStatistics(30);
        
        // Assert
        $this->assertEquals(5, $stats['total_logs']); // Only logs within 30 days
        
        $this->assertArrayHasKey('by_action', $stats);
        $this->assertEquals(3, $stats['by_action']['created']);
        $this->assertEquals(2, $stats['by_action']['updated']);
        $this->assertArrayNotHasKey('deleted', $stats['by_action']); // Outside window
        
        $this->assertArrayHasKey('by_severity', $stats);
        $this->assertEquals(3, $stats['by_severity']['info']);
        $this->assertEquals(2, $stats['by_severity']['warning']);
        
        $this->assertArrayHasKey('by_user_type', $stats);
        $this->assertEquals(3, $stats['by_user_type']['admin']);
        $this->assertEquals(2, $stats['by_user_type']['client']);
        
        $this->assertArrayHasKey('by_day', $stats);
    }

    /** @test */
    public function it_gets_most_active_users()
    {
        // Arrange
        AuditLog::factory()->count(5)->create([
            'user_id' => 1,
            'user_type' => 'admin',
            'created_at' => now()->subDays(5),
        ]);
        
        AuditLog::factory()->count(3)->create([
            'user_id' => 2,
            'user_type' => 'client',
            'created_at' => now()->subDays(10),
        ]);
        
        AuditLog::factory()->count(2)->create([
            'user_id' => 3,
            'user_type' => 'agency',
            'created_at' => now()->subDays(15),
        ]);
        
        // Act
        $activeUsers = $this->analyzer->getMostActiveUsers(30, 5);
        
        // Assert
        $this->assertCount(3, $activeUsers);
        
        // Should be ordered by activity count (descending)
        $this->assertEquals(1, $activeUsers[0]['user_id']);
        $this->assertEquals('admin', $activeUsers[0]['user_type']);
        $this->assertEquals(5, $activeUsers[0]['activity_count']);
        
        $this->assertEquals(2, $activeUsers[1]['user_id']);
        $this->assertEquals('client', $activeUsers[1]['user_type']);
        $this->assertEquals(3, $activeUsers[1]['activity_count']);
        
        $this->assertEquals(3, $activeUsers[2]['user_id']);
        $this->assertEquals('agency', $activeUsers[2]['user_type']);
        $this->assertEquals(2, $activeUsers[2]['activity_count']);
    }

    /** @test */
    public function it_gets_model_audit_trail()
    {
        // Arrange
        $modelType = 'App\\Models\\ContentItem';
        $modelId = 123;
        
        // Create logs for the specific model
        AuditLog::factory()->count(3)->create([
            'auditable_type' => $modelType,
            'auditable_id' => $modelId,
            'created_at' => now()->subHours(1),
        ]);
        
        // Create logs for different model (should not be included)
        AuditLog::factory()->count(2)->create([
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => 456,
            'created_at' => now()->subHours(2),
        ]);
        
        // Create logs for same type but different ID (should not be included)
        AuditLog::factory()->create([
            'auditable_type' => $modelType,
            'auditable_id' => 789,
            'created_at' => now()->subHours(3),
        ]);
        
        // Act
        $trail = $this->analyzer->getModelAuditTrail($modelType, $modelId);
        
        // Assert
        $this->assertCount(3, $trail);
        
        foreach ($trail as $log) {
            $this->assertEquals($modelType, $log->auditable_type);
            $this->assertEquals($modelId, $log->auditable_id);
        }
        
        // Should be ordered by created_at descending
        $this->assertTrue($trail[0]->created_at >= $trail[1]->created_at);
        $this->assertTrue($trail[1]->created_at >= $trail[2]->created_at);
    }

    /** @test */
    public function it_finds_suspicious_failed_login_activity()
    {
        // Arrange
        $suspiciousIp = '192.168.1.100';
        $normalIp = '192.168.1.200';
        
        // Create multiple failed logins from suspicious IP
        AuditLog::factory()->count(6)->create([
            'action' => 'login_failed',
            'ip_address' => $suspiciousIp,
            'created_at' => now()->subDays(3),
        ]);
        
        // Create normal amount of failed logins from normal IP
        AuditLog::factory()->count(3)->create([
            'action' => 'login_failed',
            'ip_address' => $normalIp,
            'created_at' => now()->subDays(2),
        ]);
        
        // Act
        $suspicious = $this->analyzer->findSuspiciousActivity(7);
        
        // Assert
        $this->assertArrayHasKey('failed_logins', $suspicious);
        $this->assertCount(1, $suspicious['failed_logins']);
        $this->assertEquals($suspiciousIp, $suspicious['failed_logins'][0]['ip_address']);
        $this->assertEquals(6, $suspicious['failed_logins'][0]['attempt_count']);
    }

    /** @test */
    public function it_finds_suspicious_mass_deletion_activity()
    {
        // Arrange
        $suspiciousUserId = 123;
        $normalUserId = 456;
        
        // Create mass deletions by suspicious user
        AuditLog::factory()->count(12)->create([
            'action' => 'deleted',
            'user_id' => $suspiciousUserId,
            'user_type' => 'admin',
            'created_at' => now()->subDays(2),
        ]);
        
        // Create normal deletions by normal user
        AuditLog::factory()->count(5)->create([
            'action' => 'deleted',
            'user_id' => $normalUserId,
            'user_type' => 'client',
            'created_at' => now()->subDays(1),
        ]);
        
        // Act
        $suspicious = $this->analyzer->findSuspiciousActivity(7);
        
        // Assert
        $this->assertArrayHasKey('mass_deletes', $suspicious);
        $this->assertCount(1, $suspicious['mass_deletes']);
        $this->assertEquals($suspiciousUserId, $suspicious['mass_deletes'][0]['user_id']);
        $this->assertEquals('admin', $suspicious['mass_deletes'][0]['user_type']);
        $this->assertEquals(12, $suspicious['mass_deletes'][0]['delete_count']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_suspicious_activity()
    {
        // Arrange - Create only normal activity
        AuditLog::factory()->count(3)->create([
            'action' => 'login_failed',
            'ip_address' => '192.168.1.1',
            'created_at' => now()->subDays(1),
        ]);
        
        AuditLog::factory()->count(5)->create([
            'action' => 'deleted',
            'user_id' => 1,
            'user_type' => 'admin',
            'created_at' => now()->subDays(1),
        ]);
        
        // Act
        $suspicious = $this->analyzer->findSuspiciousActivity(7);
        
        // Assert
        $this->assertEmpty($suspicious);
    }

    /** @test */
    public function it_respects_time_windows_in_statistics()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create logs at different times
        AuditLog::factory()->create(['created_at' => $now->copy()->subDays(5)]);  // Within window
        AuditLog::factory()->create(['created_at' => $now->copy()->subDays(15)]); // Within window
        AuditLog::factory()->create(['created_at' => $now->copy()->subDays(35)]); // Outside window
        
        // Act
        $stats = $this->analyzer->getStatistics(20); // 20 day window
        
        // Assert
        $this->assertEquals(2, $stats['total_logs']); // Only logs within 20 days
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}