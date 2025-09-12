<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuditLogFormatter;
use App\Services\AuditLogAnalyzer;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class AuditLogFormatterTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogFormatter $formatter;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->formatter = new AuditLogFormatter();
    }

    /** @test */
    public function it_formats_user_display_name_for_magic_link()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'magic_link',
            'user_id' => 123,
        ]);
        
        // Act
        $displayName = $this->formatter->getUserDisplayName($auditLog);
        
        // Assert
        $this->assertEquals('Client Access (ID: 123)', $displayName);
    }

    /** @test */
    public function it_formats_user_display_name_for_anonymous()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'anonymous',
            'user_id' => null,
        ]);
        
        // Act
        $displayName = $this->formatter->getUserDisplayName($auditLog);
        
        // Assert
        $this->assertEquals('Anonymous User', $displayName);
    }

    /** @test */
    public function it_formats_user_display_name_with_actual_user()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'admin',
            'user_id' => $user->id,
        ]);
        
        // Act
        $displayName = $this->formatter->getUserDisplayName($auditLog);
        
        // Assert
        $this->assertEquals('John Doe', $displayName);
    }

    /** @test */
    public function it_falls_back_to_user_id_when_user_not_found()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'admin',
            'user_id' => 999, // Non-existent user
        ]);
        
        // Act
        $displayName = $this->formatter->getUserDisplayName($auditLog);
        
        // Assert
        $this->assertEquals('User ID: 999', $displayName);
    }

    /** @test */
    public function it_gets_action_display_names()
    {
        $testCases = [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'viewed' => 'Viewed',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'commented' => 'Added Comment',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'magic_link_accessed' => 'Accessed via Magic Link',
            'trello_sync' => 'Synced to Trello',
            'export' => 'Exported Data',
            'custom_action' => 'Custom_action', // Test default case
        ];

        foreach ($testCases as $action => $expectedDisplay) {
            // Arrange
            $auditLog = AuditLog::factory()->create(['action' => $action]);
            
            // Act
            $displayName = $this->formatter->getActionDisplayName($auditLog);
            
            // Assert
            $this->assertEquals($expectedDisplay, $displayName);
        }
    }

    /** @test */
    public function it_gets_severity_colors()
    {
        $testCases = [
            'critical' => 'text-red-600 bg-red-100',
            'error' => 'text-red-600 bg-red-50',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'info' => 'text-blue-600 bg-blue-50',
            'debug' => 'text-gray-600 bg-gray-100',
            'unknown' => 'text-gray-600 bg-gray-50', // Test default case
        ];

        foreach ($testCases as $severity => $expectedColor) {
            // Arrange
            $auditLog = AuditLog::factory()->create(['severity' => $severity]);
            
            // Act
            $color = $this->formatter->getSeverityColor($auditLog);
            
            // Assert
            $this->assertEquals($expectedColor, $color);
        }
    }

    /** @test */
    public function it_gets_model_display_names()
    {
        $testCases = [
            'App\\Models\\ContentItem' => 'Content Item',
            'App\\Models\\User' => 'User',
            'App\\Models\\MagicLink' => 'Magic Link',
            'CustomModel' => 'Custom Model',
            null => 'System', // Test null case
        ];

        foreach ($testCases as $modelType => $expectedDisplay) {
            // Arrange
            $auditLog = AuditLog::factory()->create(['auditable_type' => $modelType]);
            
            // Act
            $displayName = $this->formatter->getModelDisplayName($auditLog);
            
            // Assert
            $this->assertEquals($expectedDisplay, $displayName);
        }
    }

    /** @test */
    public function it_creates_summary_with_model_and_id()
    {
        // Arrange
        $user = User::factory()->create(['name' => 'John Doe']);
        $auditLog = AuditLog::factory()->create([
            'user_id' => $user->id,
            'user_type' => 'admin',
            'action' => 'created',
            'auditable_type' => 'App\\Models\\ContentItem',
            'auditable_id' => 123,
        ]);
        
        // Act
        $summary = $this->formatter->getSummary($auditLog);
        
        // Assert
        $this->assertEquals('John Doe Created Content Item (ID: 123)', $summary);
    }

    /** @test */
    public function it_creates_summary_with_model_but_no_id()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'magic_link',
            'user_id' => 456,
            'action' => 'login',
            'auditable_type' => 'App\\Models\\User',
            'auditable_id' => null,
        ]);
        
        // Act
        $summary = $this->formatter->getSummary($auditLog);
        
        // Assert
        $this->assertEquals('Client Access (ID: 456) Logged In User', $summary);
    }

    /** @test */
    public function it_creates_summary_with_action_only()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'user_type' => 'admin',
            'user_id' => 1,
            'action' => 'export',
            'auditable_type' => null,
            'auditable_id' => null,
        ]);
        
        // Act
        $summary = $this->formatter->getSummary($auditLog);
        
        // Assert
        $this->assertStringContains('Exported Data', $summary);
        $this->assertStringNotContains('(ID:', $summary);
    }

    /** @test */
    public function it_creates_severity_badge_html()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create(['severity' => 'error']);
        
        // Act
        $badge = $this->formatter->getSeverityBadge($auditLog);
        
        // Assert
        $this->assertStringContains('<span', $badge);
        $this->assertStringContains('text-red-600 bg-red-50', $badge);
        $this->assertStringContains('Error', $badge);
        $this->assertStringContains('</span>', $badge);
    }

    /** @test */
    public function it_formats_timestamp()
    {
        // Arrange
        $timestamp = now()->setDate(2024, 3, 15)->setTime(14, 30, 45);
        $auditLog = AuditLog::factory()->create(['created_at' => $timestamp]);
        
        // Act
        $formatted = $this->formatter->getFormattedTimestamp($auditLog);
        
        // Assert
        $this->assertEquals('Mar 15, 2024 at 2:30 PM', $formatted);
    }

    /** @test */
    public function it_gets_relative_timestamp()
    {
        // Arrange
        $timestamp = now()->subHours(2);
        $auditLog = AuditLog::factory()->create(['created_at' => $timestamp]);
        
        // Act
        $relative = $this->formatter->getRelativeTimestamp($auditLog);
        
        // Assert
        $this->assertStringContains('hours ago', $relative);
    }

    /** @test */
    public function it_gets_changed_fields_summary()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['title' => 'Old Title', 'status' => 'draft'],
            'new_values' => ['title' => 'New Title', 'status' => 'published'],
        ]);

        // Mock the analyzer service
        $mockAnalyzer = Mockery::mock(AuditLogAnalyzer::class);
        $mockAnalyzer->shouldReceive('getChangedFields')
            ->with($auditLog)
            ->andReturn(['title', 'status']);
        
        $this->app->instance(AuditLogAnalyzer::class, $mockAnalyzer);
        
        // Act
        $summary = $this->formatter->getChangedFieldsSummary($auditLog);
        
        // Assert
        $this->assertCount(2, $summary);
        
        $this->assertEquals('Title', $summary[0]['field']);
        $this->assertEquals('Old Title', $summary[0]['old_value']);
        $this->assertEquals('New Title', $summary[0]['new_value']);
        
        $this->assertEquals('Status', $summary[1]['field']);
        $this->assertEquals('draft', $summary[1]['old_value']);
        $this->assertEquals('published', $summary[1]['new_value']);
    }

    /** @test */
    public function it_formats_field_names_from_snake_case()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => ['created_at' => '2024-01-01', 'user_name' => 'old'],
            'new_values' => ['created_at' => '2024-01-02', 'user_name' => 'new'],
        ]);

        $mockAnalyzer = Mockery::mock(AuditLogAnalyzer::class);
        $mockAnalyzer->shouldReceive('getChangedFields')
            ->andReturn(['created_at', 'user_name']);
        
        $this->app->instance(AuditLogAnalyzer::class, $mockAnalyzer);
        
        // Act
        $summary = $this->formatter->getChangedFieldsSummary($auditLog);
        
        // Assert
        $this->assertEquals('Created At', $summary[0]['field']);
        $this->assertEquals('User Name', $summary[1]['field']);
    }

    /** @test */
    public function it_formats_various_value_types()
    {
        // Arrange
        $auditLog = AuditLog::factory()->create([
            'old_values' => [
                'null_value' => null,
                'boolean_true' => true,
                'boolean_false' => false,
                'array_value' => ['item1', 'item2', 'item3', 'item4', 'item5'],
                'long_string' => str_repeat('a', 60),
                'normal_string' => 'normal',
            ],
            'new_values' => [
                'null_value' => 'not null',
                'boolean_true' => false,
                'boolean_false' => true,
                'array_value' => ['new1', 'new2'],
                'long_string' => 'short',
                'normal_string' => 'changed',
            ],
        ]);

        $mockAnalyzer = Mockery::mock(AuditLogAnalyzer::class);
        $mockAnalyzer->shouldReceive('getChangedFields')
            ->andReturn(['null_value', 'boolean_true', 'boolean_false', 'array_value', 'long_string', 'normal_string']);
        
        $this->app->instance(AuditLogAnalyzer::class, $mockAnalyzer);
        
        // Act
        $summary = $this->formatter->getChangedFieldsSummary($auditLog);
        
        // Assert
        // Test null formatting
        $this->assertEquals('<em>null</em>', $summary[0]['old_value']);
        
        // Test boolean formatting  
        $this->assertEquals('true', $summary[1]['old_value']);
        $this->assertEquals('false', $summary[1]['new_value']);
        $this->assertEquals('false', $summary[2]['old_value']);
        $this->assertEquals('true', $summary[2]['new_value']);
        
        // Test array formatting (should truncate to 3 items)
        $this->assertEquals('[item1, item2, item3...]', $summary[3]['old_value']);
        $this->assertEquals('[new1, new2]', $summary[3]['new_value']);
        
        // Test long string truncation
        $this->assertStringContains('...', $summary[4]['old_value']);
        $this->assertEquals(53, strlen($summary[4]['old_value'])); // 50 chars + '...'
        
        // Test normal string
        $this->assertEquals('normal', $summary[5]['old_value']);
        $this->assertEquals('changed', $summary[5]['new_value']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}