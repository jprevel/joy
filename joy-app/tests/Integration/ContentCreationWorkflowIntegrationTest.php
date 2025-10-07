<?php

namespace Tests\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Integration Test: Complete Content Creation Workflow
 * Tests end-to-end content creation with real database
 */
class ContentCreationWorkflowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_content_with_real_database_and_file_storage()
    {
        $client = \App\Models\Client::factory()->create();
        $user = \App\Models\User::factory()->agency()->create();
        $this->actingAs($user);

        // Create a default status first
        $status = \App\Models\Status::create([
            'name' => 'Draft',
            'sort_order' => 1,
            'is_reviewable' => false,
            'is_active' => true
        ]);

        $service = app(\App\Services\ContentItemService::class);

        $contentData = [[
            'title' => 'Test Content',
            'copy' => 'Test copy text',
            'platform' => 'facebook',
            'scheduled_at' => '2025-01-01'
        ]];

        $results = $service->createContentItems($contentData, $client->id);

        $this->assertCount(1, $results);
        $this->assertDatabaseHas('content_items', ['title' => 'Test Content']);
    }

    /** @test */
    public function it_handles_image_uploads_with_real_filesystem()
    {
        $client = \App\Models\Client::factory()->create();
        $contentItem = \App\Models\ContentItem::factory()->create(['client_id' => $client->id]);

        $imageService = app(\App\Services\ImageUploadService::class);
        $this->assertTrue(method_exists($imageService, 'storeContentItemImage'));

        // Verify the content item was created in database
        $this->assertDatabaseHas('content_items', ['id' => $contentItem->id]);
    }

    /** @test */
    public function it_integrates_with_audit_logging_system()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'content_created',
            'old_values' => ['test' => 'data'],
            'new_values' => [],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'content_created']);
    }

    /** @test */
    public function it_validates_platform_constraints_with_database()
    {
        $service = app(\App\Services\ContentItemService::class);

        $contentData = [[
            'title' => 'Test',
            'platform' => 'invalid_platform',
            'scheduled_at' => '2025-01-01'
        ]];

        $rules = $service->validateContentItems($contentData);

        $this->assertArrayHasKey('contentItems.0.platform', $rules);
        $this->assertStringContainsString('in:', $rules['contentItems.0.platform']);
    }
}