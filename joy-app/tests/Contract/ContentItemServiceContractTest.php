<?php

namespace Tests\Contract;

use Tests\TestCase;

/**
 * Contract Test: ContentItemService Interface
 * Tests service contracts and expected behaviors
 */
class ContentItemServiceContractTest extends TestCase
{
    /** @test */
    public function it_should_define_content_creation_interface()
    {
        $service = app(\App\Services\ContentItemService::class);

        $this->assertTrue(method_exists($service, 'createContentItems'));
        $this->assertTrue(method_exists($service, 'validateContentItems'));

        $reflection = new \ReflectionMethod($service, 'createContentItems');
        $this->assertEquals(2, $reflection->getNumberOfParameters());
    }

    /** @test */
    public function it_should_validate_platform_enum_values()
    {
        $platforms = config('platforms.available', ['facebook', 'instagram', 'linkedin', 'twitter', 'blog']);

        $this->assertIsArray($platforms);
        $this->assertContains('facebook', $platforms);
        $this->assertContains('instagram', $platforms);
        $this->assertContains('linkedin', $platforms);
        $this->assertContains('twitter', $platforms);
        $this->assertContains('blog', $platforms);
    }

    /** @test */
    public function it_should_handle_image_upload_contracts()
    {
        $imageService = app(\App\Services\ImageUploadService::class);

        $this->assertTrue(method_exists($imageService, 'storeContentItemImage'));

        $reflection = new \ReflectionMethod($imageService, 'storeContentItemImage');
        $this->assertEquals(2, $reflection->getNumberOfParameters());
    }

    /** @test */
    public function it_should_respect_status_workflow_contracts()
    {
        $contentItem = new \App\Models\ContentItem();

        $this->assertTrue(in_array('status', $contentItem->getFillable()));
        $this->assertTrue(in_array('status_id', $contentItem->getFillable()));

        $status = new \App\Models\Status();
        $this->assertTrue(in_array('name', $status->getFillable()));
        $this->assertTrue(in_array('is_active', $status->getFillable()));
    }
}