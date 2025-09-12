<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogCleanup;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class AuditLogCleanupTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogCleanup $cleanup;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cleanup = new AuditLogCleanup();
    }

    /** @test */
    public function it_cleans_up_expired_audit_logs()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create expired logs
        AuditLog::factory()->count(3)->create([
            'expires_at' => $now->copy()->subDays(1), // Expired 1 day ago
        ]);
        
        // Create non-expired logs
        AuditLog::factory()->count(2)->create([
            'expires_at' => $now->copy()->addDays(30), // Expires in 30 days
        ]);
        
        // Act
        $deletedCount = $this->cleanup->cleanupExpired();
        
        // Assert
        $this->assertEquals(3, $deletedCount);
        $this->assertDatabaseCount('audit_logs', 2); // Only non-expired logs remain
    }

    /** @test */
    public function it_cleans_up_logs_by_age()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create old logs
        AuditLog::factory()->count(4)->create([
            'created_at' => $now->copy()->subDays(100), // 100 days old
        ]);
        
        // Create recent logs
        AuditLog::factory()->count(3)->create([
            'created_at' => $now->copy()->subDays(20), // 20 days old
        ]);
        
        // Act
        $deletedCount = $this->cleanup->cleanupByAge(90); // Delete logs older than 90 days
        
        // Assert
        $this->assertEquals(4, $deletedCount);
        $this->assertDatabaseCount('audit_logs', 3); // Only recent logs remain
    }

    /** @test */
    public function it_cleans_up_logs_by_severity()
    {
        // Arrange
        AuditLog::factory()->count(3)->create(['severity' => 'debug']);
        AuditLog::factory()->count(2)->create(['severity' => 'info']);
        AuditLog::factory()->count(2)->create(['severity' => 'warning']);
        AuditLog::factory()->count(1)->create(['severity' => 'error']);
        
        // Act
        $deletedCount = $this->cleanup->cleanupBySeverity(['debug', 'info']);
        
        // Assert
        $this->assertEquals(5, $deletedCount); // 3 debug + 2 info
        $this->assertDatabaseCount('audit_logs', 3); // warning + error logs remain
        
        $remaining = AuditLog::all();
        $this->assertTrue($remaining->contains('severity', 'warning'));
        $this->assertTrue($remaining->contains('severity', 'error'));
        $this->assertFalse($remaining->contains('severity', 'debug'));
        $this->assertFalse($remaining->contains('severity', 'info'));
    }

    /** @test */
    public function it_archives_old_logs()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        $oldLogs = AuditLog::factory()->count(3)->create([
            'action' => 'test_action',
            'created_at' => $now->copy()->subDays(200),
        ]);
        
        $recentLogs = AuditLog::factory()->count(2)->create([
            'created_at' => $now->copy()->subDays(50),
        ]);
        
        // Act
        $result = $this->cleanup->archiveOldLogs(180); // Archive logs older than 180 days
        
        // Assert
        $this->assertEquals(3, $result['archived_count']);
        $this->assertEquals(3, $result['deleted_count']);
        $this->assertCount(3, $result['archive_data']);
        $this->assertDatabaseCount('audit_logs', 2); // Only recent logs remain
        
        // Verify archive data contains the correct logs
        $archivedActions = collect($result['archive_data'])->pluck('action')->toArray();
        $this->assertContains('test_action', $archivedActions);
    }

    /** @test */
    public function it_gets_cleanup_recommendations()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create expired logs
        AuditLog::factory()->count(5)->create([
            'expires_at' => $now->copy()->subDays(1),
        ]);
        
        // Create old logs (over 1 year)
        AuditLog::factory()->count(1500)->create([
            'created_at' => $now->copy()->subDays(400),
        ]);
        
        // Create many debug logs
        AuditLog::factory()->count(6000)->create([
            'severity' => 'debug',
        ]);
        
        // Create logs to exceed total count threshold
        AuditLog::factory()->count(100000)->create();
        
        // Act
        $recommendations = $this->cleanup->getCleanupRecommendations();
        
        // Assert
        $this->assertNotEmpty($recommendations);
        
        // Check for expired logs recommendation
        $expiredRec = collect($recommendations)->firstWhere('type', 'expired');
        $this->assertNotNull($expiredRec);
        $this->assertEquals('high', $expiredRec['priority']);
        $this->assertEquals(5, $expiredRec['count']);
        
        // Check for old logs recommendation
        $oldLogsRec = collect($recommendations)->firstWhere('type', 'old_logs');
        $this->assertNotNull($oldLogsRec);
        $this->assertEquals('medium', $oldLogsRec['priority']);
        
        // Check for debug logs recommendation
        $debugRec = collect($recommendations)->firstWhere('type', 'debug_logs');
        $this->assertNotNull($debugRec);
        $this->assertEquals('low', $debugRec['priority']);
        $this->assertEquals(6000, $debugRec['count']);
        
        // Check for table optimization recommendation
        $optimizeRec = collect($recommendations)->firstWhere('type', 'table_optimization');
        $this->assertNotNull($optimizeRec);
        $this->assertEquals('medium', $optimizeRec['priority']);
    }

    /** @test */
    public function it_returns_empty_recommendations_when_no_cleanup_needed()
    {
        // Arrange - create small amount of recent, non-debug logs
        AuditLog::factory()->count(10)->create([
            'severity' => 'info',
            'expires_at' => now()->addDays(30),
            'created_at' => now()->subDays(10),
        ]);
        
        // Act
        $recommendations = $this->cleanup->getCleanupRecommendations();
        
        // Assert
        $this->assertEmpty($recommendations);
    }

    /** @test */
    public function it_executes_comprehensive_cleanup()
    {
        // Arrange
        $now = Carbon::now();
        Carbon::setTestNow($now);
        
        // Create various types of logs
        AuditLog::factory()->count(2)->create(['expires_at' => $now->copy()->subDays(1)]); // Expired
        AuditLog::factory()->count(3)->create(['created_at' => $now->copy()->subDays(200)]); // Old
        AuditLog::factory()->count(4)->create(['severity' => 'debug']); // Debug severity
        AuditLog::factory()->count(2)->create(['severity' => 'info']); // Keep these
        
        $config = [
            'cleanup_expired' => true,
            'cleanup_days' => 180,
            'cleanup_severities' => ['debug'],
            'optimize_table' => false, // Skip optimization for testing
        ];
        
        // Act
        $results = $this->cleanup->executeCleanup($config);
        
        // Assert
        $this->assertEquals(2, $results['expired_deleted']);
        $this->assertEquals(3, $results['old_deleted']);
        $this->assertEquals(4, $results['severity_deleted']);
        $this->assertEquals(9, $results['total_deleted']);
        
        $this->assertDatabaseCount('audit_logs', 2); // Only info logs remain
    }

    /** @test */
    public function it_skips_cleanup_steps_based_on_configuration()
    {
        // Arrange
        AuditLog::factory()->count(2)->create(['expires_at' => now()->subDays(1)]);
        AuditLog::factory()->count(3)->create(['severity' => 'debug']);
        
        $config = [
            'cleanup_expired' => false, // Skip expired cleanup
            'optimize_table' => false,
        ];
        
        // Act
        $results = $this->cleanup->executeCleanup($config);
        
        // Assert
        $this->assertEquals(0, $results['expired_deleted']);
        $this->assertEquals(0, $results['old_deleted']);
        $this->assertEquals(0, $results['severity_deleted']);
        $this->assertEquals(0, $results['total_deleted']);
        
        $this->assertDatabaseCount('audit_logs', 5); // All logs remain
    }

    /** @test */
    public function it_gets_storage_info_for_mysql()
    {
        // Arrange
        Config::set('database.default', 'mysql');
        
        // Mock the database query result
        DB::shouldReceive('selectOne')
            ->once()
            ->with(\Mockery::pattern('/information_schema\.TABLES/'))
            ->andReturn((object) [
                'size_mb' => 15.50,
                'data_size_mb' => 12.25,
                'index_size_mb' => 3.25,
            ]);
        
        // Act
        $storageInfo = $this->cleanup->getStorageInfo();
        
        // Assert
        $this->assertEquals(15.50, $storageInfo['total_size_mb']);
        $this->assertEquals(12.25, $storageInfo['data_size_mb']);
        $this->assertEquals(3.25, $storageInfo['index_size_mb']);
    }

    /** @test */
    public function it_gets_basic_storage_info_for_non_mysql()
    {
        // Arrange
        Config::set('database.default', 'sqlite');
        AuditLog::factory()->count(1000)->create();
        
        // Act
        $storageInfo = $this->cleanup->getStorageInfo();
        
        // Assert
        $this->assertEquals(1000, $storageInfo['total_records']);
        $this->assertEquals(1.0, $storageInfo['estimated_size_mb']); // 1000 * 0.001
    }

    /** @test */
    public function it_schedules_automatic_cleanup_with_configuration()
    {
        // Arrange
        Config::set('audit.cleanup_days', 180);
        Config::set('audit.cleanup_severities', ['debug', 'info']);
        
        AuditLog::factory()->count(3)->create(['expires_at' => now()->subDays(1)]);
        AuditLog::factory()->count(2)->create(['severity' => 'debug']);
        
        // Act
        $this->cleanup->scheduleCleanup();
        
        // Assert
        // Verify cleanup was executed by checking remaining logs
        $this->assertDatabaseCount('audit_logs', 0);
    }

    /** @test */
    public function it_uses_default_configuration_when_none_provided()
    {
        // Arrange
        AuditLog::factory()->count(2)->create(['expires_at' => now()->subDays(1)]);
        
        // Act
        $this->cleanup->scheduleCleanup();
        
        // Assert
        // At minimum, expired cleanup should run
        $remainingExpired = AuditLog::where('expires_at', '<=', now())->count();
        $this->assertEquals(0, $remainingExpired);
    }

    /** @test */
    public function it_handles_empty_database_gracefully()
    {
        // Act
        $expiredDeleted = $this->cleanup->cleanupExpired();
        $ageDeleted = $this->cleanup->cleanupByAge(90);
        $severityDeleted = $this->cleanup->cleanupBySeverity(['debug']);
        $recommendations = $this->cleanup->getCleanupRecommendations();
        $archive = $this->cleanup->archiveOldLogs(365);
        
        // Assert
        $this->assertEquals(0, $expiredDeleted);
        $this->assertEquals(0, $ageDeleted);
        $this->assertEquals(0, $severityDeleted);
        $this->assertEmpty($recommendations);
        $this->assertEquals(0, $archive['archived_count']);
        $this->assertEquals(0, $archive['deleted_count']);
        $this->assertEmpty($archive['archive_data']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon test time
        parent::tearDown();
    }
}