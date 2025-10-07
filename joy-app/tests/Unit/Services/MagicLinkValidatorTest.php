<?php

namespace Tests\Unit\Services;

use App\Models\MagicLink;
use App\Models\Client;
use App\Services\MagicLinkValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit Test: MagicLinkValidator
 * Tests token validation and security logic
 */
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
    public function it_validates_token_format()
    {
        $client = Client::factory()->create();
        $validToken = 'valid_token_123';

        $magicLink = MagicLink::factory()->create([
            'client_id' => $client->id,
            'token' => $validToken,
            'expires_at' => now()->addDays(7),
        ]);

        $result = $this->validator->validateToken($validToken);

        $this->assertInstanceOf(MagicLink::class, $result);
        $this->assertEquals($validToken, $result->token);
    }

    /** @test */
    public function it_checks_token_expiration()
    {
        $client = Client::factory()->create();

        // Create expired magic link
        $expiredLink = MagicLink::factory()->create([
            'client_id' => $client->id,
            'token' => 'expired_token',
            'expires_at' => now()->subDays(1),
        ]);

        // Create valid magic link
        $validLink = MagicLink::factory()->create([
            'client_id' => $client->id,
            'token' => 'valid_token',
            'expires_at' => now()->addDays(7),
        ]);

        // Expired token should not validate
        $this->assertNull($this->validator->validateToken('expired_token'));

        // Valid token should validate
        $this->assertInstanceOf(MagicLink::class, $this->validator->validateToken('valid_token'));

        // Test isValid method
        $this->assertFalse($this->validator->isValid($expiredLink));
        $this->assertTrue($this->validator->isValid($validLink));
    }

    /** @test */
    public function it_validates_access_permissions()
    {
        $client = Client::factory()->create();

        $magicLink = MagicLink::factory()->create([
            'client_id' => $client->id,
            'token' => 'test_token',
            'expires_at' => now()->addDays(7),
        ]);

        // Test workspace access
        $hasAccess = $this->validator->hasWorkspaceAccess($magicLink, $client->id);
        $this->assertTrue($hasAccess);

        // Test access to different workspace
        $noAccess = $this->validator->hasWorkspaceAccess($magicLink, 999);
        $this->assertFalse($noAccess);
    }

    /** @test */
    public function it_logs_security_events()
    {
        $client = Client::factory()->create();

        $magicLink = MagicLink::factory()->create([
            'client_id' => $client->id,
            'token' => 'test_token',
            'expires_at' => now()->addDays(7),
        ]);

        // This should not throw an exception
        $this->validator->logAccess($magicLink, 'view_content', ['item_id' => 123]);

        // Verify logging doesn't break the flow
        $this->assertTrue(true);
    }
}
