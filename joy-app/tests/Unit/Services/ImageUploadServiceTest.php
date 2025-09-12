<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ImageUploadService;
use App\Models\ContentItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

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
    public function it_stores_valid_image_for_content_item()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        // Act
        $path = $this->service->storeContentItemImage($contentItem, $file);
        
        // Assert
        $this->assertNotNull($path);
        $this->assertStringContains('content-images/', $path);
        Storage::disk('public')->assertExists($path);
        
        $contentItem->refresh();
        $this->assertEquals($path, $contentItem->image_path);
    }

    /** @test */
    public function it_generates_unique_filename_for_uploads()
    {
        // Arrange
        $contentItem1 = ContentItem::factory()->create();
        $contentItem2 = ContentItem::factory()->create();
        $file1 = UploadedFile::fake()->image('test.jpg');
        $file2 = UploadedFile::fake()->image('test.jpg');
        
        // Act
        $path1 = $this->service->storeContentItemImage($contentItem1, $file1);
        $path2 = $this->service->storeContentItemImage($contentItem2, $file2);
        
        // Assert
        $this->assertNotEquals($path1, $path2);
        $this->assertStringContains('test_', basename($path1));
        $this->assertStringContains('test_', basename($path2));
    }

    /** @test */
    public function it_rejects_oversized_files()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        // Create a file larger than 10MB (10240 KB)
        $file = UploadedFile::fake()->create('huge.jpg', 11000); // 11MB
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image file provided');
        
        $this->service->storeContentItemImage($contentItem, $file);
    }

    /** @test */
    public function it_rejects_invalid_file_extensions()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image file provided');
        
        $this->service->storeContentItemImage($contentItem, $file);
    }

    /** @test */
    public function it_accepts_valid_image_extensions()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        foreach ($extensions as $ext) {
            // Act
            $file = UploadedFile::fake()->image("test.{$ext}");
            
            // Assert - should not throw exception
            $path = $this->service->storeContentItemImage($contentItem, $file);
            $this->assertNotNull($path);
            
            // Clean up
            $contentItem->update(['image_path' => null]);
        }
    }

    /** @test */
    public function it_deletes_content_item_image_successfully()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg');
        $path = $this->service->storeContentItemImage($contentItem, $file);
        
        // Act
        $result = $this->service->deleteContentItemImage($contentItem);
        
        // Assert
        $this->assertTrue($result);
        Storage::disk('public')->assertMissing($path);
        
        $contentItem->refresh();
        $this->assertNull($contentItem->image_path);
    }

    /** @test */
    public function it_handles_deleting_non_existent_image_gracefully()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create(['image_path' => null]);
        
        // Act
        $result = $this->service->deleteContentItemImage($contentItem);
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_returns_public_url_for_image_path()
    {
        // Arrange
        $imagePath = 'content-images/test_image.jpg';
        Storage::disk('public')->put($imagePath, 'fake image content');
        
        // Act
        $url = $this->service->getImageUrl($imagePath);
        
        // Assert
        $this->assertNotNull($url);
        $this->assertStringContains('storage/' . $imagePath, $url);
    }

    /** @test */
    public function it_returns_null_for_empty_image_path()
    {
        // Act
        $url = $this->service->getImageUrl(null);
        
        // Assert
        $this->assertNull($url);
    }

    /** @test */
    public function it_returns_formatted_image_size()
    {
        // Arrange
        $imagePath = 'content-images/test_image.jpg';
        $content = str_repeat('x', 1024 * 5); // 5KB
        Storage::disk('public')->put($imagePath, $content);
        
        // Act
        $size = $this->service->getImageSize($imagePath);
        
        // Assert
        $this->assertStringContains('KB', $size);
        $this->assertStringContains('5', $size);
    }

    /** @test */
    public function it_returns_null_size_for_non_existent_image()
    {
        // Act
        $size = $this->service->getImageSize('non-existent.jpg');
        
        // Assert
        $this->assertNull($size);
    }

    /** @test */
    public function it_formats_bytes_to_human_readable_format()
    {
        // This tests the private formatBytes method through getImageSize
        // Arrange - create files of different sizes
        $testCases = [
            ['size' => 500, 'expected' => 'B'],           // 500 bytes
            ['size' => 1024 * 2, 'expected' => 'KB'],     // 2 KB  
            ['size' => 1024 * 1024 * 3, 'expected' => 'MB'], // 3 MB
        ];
        
        foreach ($testCases as $case) {
            $imagePath = "content-images/test_{$case['size']}.jpg";
            $content = str_repeat('x', $case['size']);
            Storage::disk('public')->put($imagePath, $content);
            
            // Act
            $size = $this->service->getImageSize($imagePath);
            
            // Assert
            $this->assertStringContains($case['expected'], $size);
        }
    }

    /** @test */
    public function it_validates_actual_image_content()
    {
        // Arrange
        $contentItem = ContentItem::factory()->create();
        // Create a fake image file that's actually just text
        $file = UploadedFile::fake()->create('fake.jpg', 100);
        
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid image file provided');
        
        $this->service->storeContentItemImage($contentItem, $file);
    }
}