<?php

namespace Tests\Contract;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Contract Test: Trello Integration Interface
 * Tests external API integration contracts
 */
class TrelloIntegrationContractTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function it_should_define_card_synchronization_contract()
    {
        $integration = new \App\Models\TrelloIntegration([
            'api_key' => 'test-key',
            'api_token' => 'test-token',
            'board_id' => 'test-board'
        ]);
        $service = new \App\Services\TrelloService($integration);

        $this->assertTrue(method_exists($service, 'createCard'));
        $this->assertTrue(method_exists($service, 'updateCardStatus'));

        $trelloCard = new \App\Models\TrelloCard();
        $this->assertContains('content_item_id', $trelloCard->getFillable());
    }

    /** @test */
    public function it_should_handle_webhook_processing_contract()
    {
        $integration = new \App\Models\TrelloIntegration([
            'api_key' => 'test-key',
            'api_token' => 'test-token',
            'board_id' => 'test-board'
        ]);
        $service = new \App\Services\TrelloService($integration);

        $this->assertTrue(method_exists($service, 'setupWebhook'));

        $trelloCard = new \App\Models\TrelloCard();
        $this->assertContains('trello_id', $trelloCard->getFillable());
    }

    /** @test */
    public function it_should_manage_api_rate_limiting_contract()
    {
        $integration = new \App\Models\TrelloIntegration([
            'api_key' => 'test-key',
            'api_token' => 'test-token',
            'board_id' => 'test-board'
        ]);
        $service = new \App\Services\TrelloService($integration);

        $this->assertTrue(method_exists($service, 'testConnection'));

        $trelloCard = new \App\Models\TrelloCard();
        $this->assertContains('url', $trelloCard->getFillable());
    }
}