<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MagicLinkValidator;
use App\Models\MagicLink;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Mockery;

class MagicLinkValidatorTest extends TestCase
{
    use RefreshDatabase;

    private MagicLinkValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validator = new MagicLinkValidator();
    }

    /** @test */
    public function it_validates_magic_link_from_request_attributes()
    {
        // Arrange
        $magicLink = MagicLink::factory()->create();
        $request = new Request();
        $request->attributes->set('magic_link', $magicLink);
        
        // Act
        $result = $this->validator->validateFromRequest($request);
        
        // Assert
        $this->assertInstanceOf(MagicLink::class, $result);
        $this->assertEquals($magicLink->id, $result->id);
    }

    /** @test */
    public function it_returns_null_when_no_magic_link_in_request()
    {
        // Arrange
        $request = new Request();
        
        // Act
        $result = $this->validator->validateFromRequest($request);
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_validates_active_non_expired_token()
    {
        // Arrange
        $magicLink = MagicLink::factory()->create([
            'token' => 'valid-token-123',
            'is_active' => true,
            'expires_at' => now()->addHours(24),
        ]);
        
        // Act
        $result = $this->validator->validateToken('valid-token-123');
        
        // Assert
        $this->assertInstanceOf(MagicLink::class, $result);
        $this->assertEquals($magicLink->id, $result->id);
    }

    /** @test */
    public function it_rejects_expired_token()
    {
        // Arrange
        MagicLink::factory()->create([
            'token' => 'expired-token',
            'is_active' => true,
            'expires_at' => now()->subHours(1), // Expired 1 hour ago
        ]);
        
        // Act
        $result = $this->validator->validateToken('expired-token');
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_rejects_inactive_token()
    {
        // Arrange
        MagicLink::factory()->create([
            'token' => 'inactive-token',
            'is_active' => false,
            'expires_at' => now()->addHours(24),
        ]);
        
        // Act
        $result = $this->validator->validateToken('inactive-token');
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_rejects_non_existent_token()
    {
        // Act
        $result = $this->validator->validateToken('non-existent-token');
        
        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_checks_if_magic_link_is_valid()
    {
        // Arrange - Valid magic link
        $validMagicLink = MagicLink::factory()->create([
            'is_active' => true,
            'expires_at' => now()->addHours(24),
        ]);
        
        // Act & Assert
        $this->assertTrue($this->validator->isValid($validMagicLink));
        
        // Arrange - Expired magic link
        $expiredMagicLink = MagicLink::factory()->create([
            'is_active' => true,
            'expires_at' => now()->subHours(1),
        ]);
        
        // Act & Assert
        $this->assertFalse($this->validator->isValid($expiredMagicLink));
        
        // Arrange - Inactive magic link
        $inactiveMagicLink = MagicLink::factory()->create([
            'is_active' => false,
            'expires_at' => now()->addHours(24),
        ]);
        
        // Act & Assert
        $this->assertFalse($this->validator->isValid($inactiveMagicLink));
    }

    /** @test */
    public function it_checks_workspace_access()
    {
        // Arrange
        $workspace = Workspace::factory()->create();
        $magicLink = MagicLink::factory()->create(['workspace_id' => $workspace->id]);
        
        // Act & Assert
        $this->assertTrue($this->validator->hasWorkspaceAccess($magicLink, $workspace->id));
        $this->assertFalse($this->validator->hasWorkspaceAccess($magicLink, 999)); // Different workspace
    }

    /** @test */
    public function it_returns_error_response_for_invalid_link()
    {
        // Act
        $response = $this->validator->getInvalidLinkResponse('Custom error message');
        
        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContains('Custom error message', $response->getContent());
    }

    /** @test */
    public function it_validates_or_fails_with_valid_magic_link()
    {
        // Arrange
        $magicLink = MagicLink::factory()->create();
        $request = new Request();
        $request->attributes->set('magic_link', $magicLink);
        
        // Act
        $result = $this->validator->validateOrFail($request);
        
        // Assert
        $this->assertInstanceOf(MagicLink::class, $result);
        $this->assertEquals($magicLink->id, $result->id);
    }

    /** @test */
    public function it_aborts_when_no_magic_link_present()
    {
        // Arrange
        $request = new Request();
        
        // Act & Assert
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        $this->validator->validateOrFail($request);
    }

    /** @test */
    public function it_checks_content_item_access_through_workspace()
    {
        // Arrange
        $workspace = Workspace::factory()->create();
        $magicLink = MagicLink::factory()->create(['workspace_id' => $workspace->id]);
        
        $contentItem = Mockery::mock();
        $contentItem->workspace = $workspace;
        $contentItem->shouldReceive('workspace')->andReturn($workspace);
        
        // Act
        $result = $this->validator->canAccessContentItem($magicLink, $contentItem);
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_checks_content_item_access_through_concept_relationship()
    {
        // Arrange
        $workspace = Workspace::factory()->create();
        $magicLink = MagicLink::factory()->create(['workspace_id' => $workspace->id]);
        
        $concept = Mockery::mock();
        $concept->workspace_id = $workspace->id;
        
        $contentItem = Mockery::mock();
        $contentItem->concept = $concept;
        $contentItem->shouldReceive('concept')->andReturn($concept);
        
        // Act
        $result = $this->validator->canAccessContentItem($magicLink, $contentItem);
        
        // Assert
        $this->assertTrue($result);
    }

    /** @test */
    public function it_denies_access_to_content_from_different_workspace()
    {
        // Arrange
        $workspace1 = Workspace::factory()->create();
        $workspace2 = Workspace::factory()->create();
        $magicLink = MagicLink::factory()->create(['workspace_id' => $workspace1->id]);
        
        $contentItem = Mockery::mock();
        $contentItem->workspace = $workspace2;
        $contentItem->shouldReceive('workspace')->andReturn($workspace2);
        
        // Act
        $result = $this->validator->canAccessContentItem($magicLink, $contentItem);
        
        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_logs_magic_link_access()
    {
        // Arrange
        $magicLink = MagicLink::factory()->create();
        
        // Mock the request for IP and user agent
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('HTTP_USER_AGENT', 'Test Browser');
        $this->app->instance('request', $request);
        
        // Act
        $this->validator->logAccess($magicLink, 'dashboard_access', ['page' => 'dashboard']);
        
        // Assert - This would typically check log files or a logging mock
        // For now, we're just verifying it doesn't throw an exception
        $this->assertTrue(true);
    }

    /** @test */
    public function it_uses_default_error_message_when_none_provided()
    {
        // Act
        $response = $this->validator->getInvalidLinkResponse();
        
        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContains('Invalid access', $response->getContent());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}