<?php

namespace Tests\Unit\Services;

use Tests\TestCase;

/**
 * Unit Test: ContentItemService
 * Tests individual service methods
 */
class ContentItemServiceTest extends TestCase
{
    /** @test */
    public function it_validates_content_item_data_structure()
    {
        $service = new \App\Services\ContentItemService(app(\App\Services\ImageUploadService::class));

        $contentData = [['title' => 'Test', 'platform' => 'facebook', 'scheduled_at' => '2025-01-01']];
        $rules = $service->validateContentItems($contentData);

        $this->assertArrayHasKey('contentItems.0.title', $rules);
        $this->assertArrayHasKey('contentItems.0.platform', $rules);
        $this->assertArrayHasKey('contentItems.0.scheduled_at', $rules);
    }

    /** @test */
    public function it_processes_platform_specific_requirements()
    {
        $service = new \App\Services\ContentItemService(app(\App\Services\ImageUploadService::class));
        $platforms = config('platforms.available', ['Facebook', 'Instagram', 'LinkedIn', 'Twitter', 'Blog']);

        $contentData = [['title' => 'Test', 'platform' => 'facebook', 'scheduled_at' => '2025-01-01']];
        $rules = $service->validateContentItems($contentData);

        $this->assertStringContainsString('in:', $rules['contentItems.0.platform']);
    }

    /** @test */
    public function it_handles_scheduled_date_formatting()
    {
        $service = new \App\Services\ContentItemService(app(\App\Services\ImageUploadService::class));

        $contentData = [['title' => 'Test', 'platform' => 'facebook', 'scheduled_at' => '2025-01-01']];
        $rules = $service->validateContentItems($contentData);

        $this->assertStringContainsString('date_format:Y-m-d', $rules['contentItems.0.scheduled_at']);
    }

    /** @test */
    public function it_manages_content_status_transitions()
    {
        $contentItem = new \App\Models\ContentItem(['status' => 'draft']);

        $this->assertEquals('draft', $contentItem->status);
        $this->assertTrue(in_array('status', $contentItem->getFillable()));
        $this->assertTrue(in_array('status_id', $contentItem->getFillable()));
    }
}