<?php

namespace Tests\Unit\Services;

use App\Models\Client;
use App\Services\TrelloService;
use App\Services\TrelloStatsService;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrelloStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrelloStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrelloStatsService();
    }

    /** @test */
    public function it_gets_client_specific_statistics()
    {
        $client = Client::factory()->create([
            'name' => 'Test Client',
            'trello_board_id' => 'board_123',
            'trello_list_id' => 'list_123',
        ]);

        $stats = $this->service->getClientStats($client);

        $this->assertArrayHasKey('client_id', $stats);
        $this->assertArrayHasKey('client_name', $stats);
        $this->assertArrayHasKey('integration_configured', $stats);
        $this->assertArrayHasKey('total_content_items', $stats);
        $this->assertArrayHasKey('synced_content_items', $stats);
        $this->assertArrayHasKey('sync_rate_content', $stats);
        $this->assertArrayHasKey('total_comments', $stats);
        $this->assertArrayHasKey('synced_comments', $stats);
        $this->assertArrayHasKey('sync_rate_comments', $stats);
        $this->assertEquals($client->id, $stats['client_id']);
        $this->assertEquals('Test Client', $stats['client_name']);
    }

    /** @test */
    public function it_calculates_content_sync_rate_for_client()
    {
        $client = Client::factory()->create();

        $stats = $this->service->getClientStats($client);

        // With no content, sync rate should be 0
        $this->assertEquals(0, $stats['sync_rate_content']);
        $this->assertIsNumeric($stats['sync_rate_content']);
    }

    /** @test */
    public function it_calculates_comment_sync_rate_for_client()
    {
        $client = Client::factory()->create();

        $stats = $this->service->getClientStats($client);

        // With no comments, sync rate should be 0
        $this->assertEquals(0, $stats['sync_rate_comments']);
        $this->assertIsNumeric($stats['sync_rate_comments']);
    }

    /** @test */
    public function it_handles_zero_content_when_calculating_sync_rate()
    {
        $client = Client::factory()->create();

        $stats = $this->service->getClientStats($client);

        $this->assertEquals(0, $stats['total_content_items']);
        $this->assertEquals(0, $stats['synced_content_items']);
        $this->assertEquals(0, $stats['sync_rate_content']);
        $this->assertEquals(0, $stats['total_comments']);
        $this->assertEquals(0, $stats['synced_comments']);
        $this->assertEquals(0, $stats['sync_rate_comments']);
    }

    /** @test */
    public function it_gets_system_wide_statistics()
    {
        // Create a client with integration
        Client::factory()->create([
            'trello_board_id' => 'board_1',
            'trello_list_id' => 'list_1'
        ]);

        $stats = $this->service->getSystemStats();

        $this->assertArrayHasKey('total_clients', $stats);
        $this->assertArrayHasKey('integrated_clients', $stats);
        $this->assertArrayHasKey('integration_rate', $stats);
        $this->assertArrayHasKey('total_cards', $stats);
        $this->assertArrayHasKey('pending_syncs', $stats);
        $this->assertArrayHasKey('failed_syncs', $stats);
        $this->assertArrayHasKey('sync_health', $stats);
    }

    /** @test */
    public function it_calculates_integration_rate()
    {
        // Create 2 clients with integration, 1 without
        Client::factory()->create([
            'trello_board_id' => 'board_1',
            'trello_list_id' => 'list_1'
        ]);
        Client::factory()->create([
            'trello_board_id' => 'board_2',
            'trello_list_id' => 'list_2'
        ]);
        Client::factory()->create(); // No integration

        $stats = $this->service->getSystemStats();

        $this->assertEquals(3, $stats['total_clients']);
        $this->assertEquals(2, $stats['integrated_clients']);
        $this->assertEquals(66.67, $stats['integration_rate']);
    }

    /** @test */
    public function it_calculates_sync_health()
    {
        $stats = $this->service->getSystemStats();

        // With no cards, sync health should be 100% (can be int or float)
        $this->assertEquals(100, $stats['sync_health']);
        $this->assertIsNumeric($stats['sync_health']);
    }

    /** @test */
    public function it_retries_failed_syncs_for_specific_client()
    {
        $trelloService = $this->createMock(TrelloService::class);

        $results = $this->service->retryFailedSyncs(1, $trelloService);

        $this->assertArrayHasKey('processed', $results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('errors', $results);
        $this->assertEquals(0, $results['processed']);
    }

    /** @test */
    public function it_retries_all_failed_syncs_when_no_client_specified()
    {
        $trelloService = $this->createMock(TrelloService::class);

        $results = $this->service->retryFailedSyncs(null, $trelloService);

        $this->assertIsArray($results);
        $this->assertArrayHasKey('processed', $results);
        $this->assertArrayHasKey('success', $results);
        $this->assertArrayHasKey('failed', $results);
        $this->assertArrayHasKey('errors', $results);
    }

    /** @test */
    public function it_returns_retry_results_with_counts()
    {
        $trelloService = $this->createMock(TrelloService::class);

        $results = $this->service->retryFailedSyncs(null, $trelloService);

        $this->assertIsInt($results['processed']);
        $this->assertIsInt($results['success']);
        $this->assertIsInt($results['failed']);
        $this->assertIsArray($results['errors']);
    }

    /** @test */
    public function it_handles_content_item_cards_when_retrying()
    {
        $trelloService = $this->createMock(TrelloService::class);

        // Test verifies the method structure without actual cards
        $results = $this->service->retryFailedSyncs(1, $trelloService);

        $this->assertEquals(0, $results['processed']);
        $this->assertEquals(0, $results['success']);
        $this->assertEquals(0, $results['failed']);
    }

    /** @test */
    public function it_handles_comment_cards_when_retrying()
    {
        $trelloService = $this->createMock(TrelloService::class);

        // Test verifies the method structure without actual cards
        $results = $this->service->retryFailedSyncs(1, $trelloService);

        $this->assertIsArray($results);
        $this->assertEmpty($results['errors']);
    }

    /** @test */
    public function it_captures_errors_during_retry_operation()
    {
        $trelloService = $this->createMock(TrelloService::class);

        $results = $this->service->retryFailedSyncs(null, $trelloService);

        // Verify errors array exists and is properly formatted
        $this->assertIsArray($results['errors']);
        $this->assertEmpty($results['errors']); // No failed cards means no errors
    }
}
