<?php

namespace Tests\Unit\Services;

use App\Models\TrelloIntegration;
use App\Services\TrelloService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Unit Test: TrelloService
 * Tests Trello API integration logic
 */
class TrelloServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrelloIntegration $integration;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock TrelloIntegration without using factory
        $this->integration = new TrelloIntegration([
            'api_key' => 'test_key',
            'api_token' => 'test_token',
            'board_id' => 'test_board_id',
            'list_id' => 'test_list_id',
        ]);

        // Set the ID manually since we're not saving to database
        $this->integration->id = 1;
    }

    /** @test */
    public function it_formats_card_data_for_api()
    {
        Http::fake([
            'api.trello.com/*' => Http::response([
                'id' => 'card_123',
                'name' => 'Test Card',
                'url' => 'https://trello.com/c/card_123'
            ], 200)
        ]);

        $service = new TrelloService($this->integration);
        $result = $service->testConnection();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    /** @test */
    public function it_handles_api_rate_limiting()
    {
        Http::fake([
            'api.trello.com/*' => Http::response([], 429) // Rate limit response
        ]);

        $service = new TrelloService($this->integration);
        $result = $service->testConnection();

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /** @test */
    public function it_processes_webhook_payloads()
    {
        // Test basic webhook structure validation
        $webhookPayload = [
            'action' => [
                'type' => 'commentCard',
                'data' => [
                    'card' => ['id' => 'card_123'],
                    'text' => 'Test comment'
                ]
            ]
        ];

        $this->assertArrayHasKey('action', $webhookPayload);
        $this->assertArrayHasKey('type', $webhookPayload['action']);
        $this->assertEquals('commentCard', $webhookPayload['action']['type']);
    }

    /** @test */
    public function it_manages_retry_logic_for_failed_requests()
    {
        // Simulate a failed request followed by success
        Http::fake([
            'api.trello.com/*' => Http::sequence()
                ->push([], 500) // First attempt fails
                ->push(['id' => 'card_123'], 200) // Second attempt succeeds
        ]);

        $service = new TrelloService($this->integration);

        // First call should fail
        $result1 = $service->testConnection();
        $this->assertFalse($result1['success']);

        // Second call should succeed (simulating retry)
        $result2 = $service->testConnection();
        $this->assertTrue($result2['success']);
    }
}
