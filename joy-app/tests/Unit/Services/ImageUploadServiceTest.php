<?php

namespace Tests\Unit\Services;

use App\Models\ContentItem;
use App\Models\Client;
use App\Services\ImageUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Unit Test: ImageUploadService
 * Tests image processing and validation
 */
class ImageUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private ImageUploadService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImageUploadService();
        Storage::fake('public');
    }

    /** @test */
    public function it_validates_image_file_types()
    {
        $client = Client::factory()->create();
        $contentItem = ContentItem::factory()->create(['client_id' => $client->id]);

        // Valid image types
        $validImage = UploadedFile::fake()->image('test.jpg');
        $result = $this->service->storeContentItemImage($contentItem, $validImage);
        $this->assertNotNull($result);
        $this->assertStringContainsString('.jpg', $result);

        // Test validation throws exception for invalid types
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->storeContentItemImage($contentItem, $invalidFile);
    }

    /** @test */
    public function it_validates_image_file_sizes()
    {
        $client = Client::factory()->create();
        $contentItem = ContentItem::factory()->create(['client_id' => $client->id]);

        // Valid size (1MB)
        $validImage = UploadedFile::fake()->image('test.jpg')->size(1024);
        $result = $this->service->storeContentItemImage($contentItem, $validImage);
        $this->assertNotNull($result);

        // File too large (15MB - exceeds 10MB limit)
        $largeImage = UploadedFile::fake()->image('large.jpg')->size(15360);
        $this->expectException(\InvalidArgumentException::class);
        $this->service->storeContentItemImage($contentItem, $largeImage);
    }

    /** @test */
    public function it_generates_unique_filenames()
    {
        $client = Client::factory()->create();
        $contentItem1 = ContentItem::factory()->create(['client_id' => $client->id]);
        $contentItem2 = ContentItem::factory()->create(['client_id' => $client->id]);

        $image1 = UploadedFile::fake()->image('test.jpg');
        $image2 = UploadedFile::fake()->image('test.jpg');

        $path1 = $this->service->storeContentItemImage($contentItem1, $image1);
        $path2 = $this->service->storeContentItemImage($contentItem2, $image2);

        // Filenames should be different even for same original name
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContainsString('test', $path1);
        $this->assertStringContainsString('test', $path2);
    }

    /** @test */
    public function it_handles_upload_error_scenarios()
    {
        $client = Client::factory()->create();
        $contentItem = ContentItem::factory()->create(['client_id' => $client->id]);

        // Test deleting non-existent image (should not throw error)
        $result = $this->service->deleteContentItemImage($contentItem);
        $this->assertTrue($result);

        // Test getting URL for null path
        $url = $this->service->getImageUrl(null);
        $this->assertNull($url);

        // Test getting size for non-existent image
        $size = $this->service->getImageSize('non-existent.jpg');
        $this->assertNull($size);
    }
}
