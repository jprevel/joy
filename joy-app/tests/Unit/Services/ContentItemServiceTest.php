<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ContentItemService;
use App\Services\ImageUploadService;
use App\Models\ContentItem;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Mockery;

class ContentItemServiceTest extends TestCase
{
    use RefreshDatabase;

    private ContentItemService $service;
    private $mockImageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockImageService = Mockery::mock(ImageUploadService::class);
        $this->service = new ContentItemService($this->mockImageService);
    }

    /** @test */
    public function it_creates_multiple_content_items_from_form_data()
    {
        // Arrange
        $this->createDefaultStatus();
        $contentItems = [
            [
                'title' => 'Test Content 1',
                'copy' => 'Test copy content',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ],
            [
                'title' => 'Test Content 2',
                'copy' => 'Another test copy',
                'platform' => 'Instagram', 
                'scheduled_at' => '2024-01-16',
            ]
        ];
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(ContentItem::class, $result[0]);
        $this->assertEquals('Test Content 1', $result[0]->title);
        $this->assertEquals('Test Content 2', $result[1]->title);
        $this->assertDatabaseCount('content_items', 2);
    }

    /** @test */
    public function it_handles_image_uploads_when_present()
    {
        // Arrange
        $this->createDefaultStatus();
        $mockFile = UploadedFile::fake()->image('test.jpg');
        $contentItems = [
            [
                'title' => 'Test Content',
                'copy' => 'Test copy',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
                'image' => $mockFile,
            ]
        ];

        $this->mockImageService
            ->shouldReceive('storeContentItemImage')
            ->once()
            ->with(Mockery::type(ContentItem::class), $mockFile)
            ->andReturn('path/to/image.jpg');
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $this->assertCount(1, $result);
    }

    /** @test */
    public function it_validates_content_items_data()
    {
        // Arrange
        $contentItems = [
            [
                'title' => 'Test',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ]
        ];
        
        // Act
        $rules = $this->service->validateContentItems($contentItems);
        
        // Assert
        $this->assertArrayHasKey('contentItems.0.title', $rules);
        $this->assertArrayHasKey('contentItems.0.platform', $rules);
        $this->assertArrayHasKey('contentItems.0.scheduled_at', $rules);
        $this->assertEquals('required|string|max:255', $rules['contentItems.0.title']);
    }

    /** @test */
    public function it_uses_configuration_for_platform_validation()
    {
        // Arrange
        config(['platforms.available' => ['Facebook', 'Instagram', 'Twitter']]);
        $contentItems = [['platform' => 'Facebook']];
        
        // Act
        $rules = $this->service->validateContentItems($contentItems);
        
        // Assert
        $this->assertStringContains('Facebook,Instagram,Twitter', $rules['contentItems.0.platform']);
    }

    /** @test */
    public function it_formats_scheduled_dates_correctly()
    {
        // Arrange
        $this->createDefaultStatus();
        $contentItems = [
            [
                'title' => 'Test Content',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ]
        ];
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $scheduledAt = $result[0]->scheduled_at;
        $this->assertInstanceOf(Carbon::class, $scheduledAt);
        $this->assertEquals('2024-01-15 00:00:00', $scheduledAt->format('Y-m-d H:i:s'));
    }

    /** @test */
    public function it_assigns_default_status_when_available()
    {
        // Arrange
        $status = $this->createDefaultStatus();
        $contentItems = [
            [
                'title' => 'Test Content',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ]
        ];
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $this->assertEquals($status->id, $result[0]->status_id);
        $this->assertEquals('Draft', $result[0]->status);
    }

    /** @test */
    public function it_handles_missing_default_status_gracefully()
    {
        // Arrange - no default status created
        $contentItems = [
            [
                'title' => 'Test Content',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ]
        ];
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $this->assertNull($result[0]->status_id);
        $this->assertEquals('Draft', $result[0]->status);
    }

    /** @test */
    public function it_assigns_owner_id_correctly()
    {
        // Arrange
        $this->createDefaultStatus();
        $contentItems = [
            [
                'title' => 'Test Content',
                'platform' => 'Facebook',
                'scheduled_at' => '2024-01-15',
            ]
        ];
        
        // Act
        $result = $this->service->createContentItems($contentItems, 1);
        
        // Assert
        $this->assertEquals(1, $result[0]->owner_id); // TODO: Replace with actual owner logic
    }

    private function createDefaultStatus(): Status
    {
        return Status::factory()->create(['name' => 'Draft']);
    }
}