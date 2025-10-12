<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

/**
 * Test helper trait for Slack notification assertions
 *
 * This is a trait, NOT a test file, so it doesn't count toward test lock
 */
trait SlackNotificationAssertions
{
    /**
     * Mock Slack API with successful responses
     */
    protected function mockSlackApiSuccess(): void
    {
        Http::fake([
            'slack.com/api/auth.test' => Http::response([
                'ok' => true,
                'user' => 'test-bot',
            ], 200),
            'slack.com/api/conversations.list' => Http::response([
                'ok' => true,
                'channels' => [
                    ['id' => 'C123456', 'name' => 'test-channel', 'is_archived' => false],
                ],
            ], 200),
            'slack.com/api/chat.postMessage' => Http::response([
                'ok' => true,
                'ts' => '1234567890.123456',
                'channel' => 'C123456',
            ], 200),
        ]);
    }

    /**
     * Mock Slack API with failure responses
     */
    protected function mockSlackApiFailure(): void
    {
        Http::fake([
            'slack.com/api/*' => Http::response([
                'ok' => false,
                'error' => 'channel_not_found',
            ], 404),
        ]);
    }

    /**
     * Fake the queue for testing job dispatching
     */
    protected function fakeQueue(): void
    {
        Queue::fake();
    }

    /**
     * Assert Slack API was called with specific URL
     */
    protected function assertSlackApiCalled(string $endpoint): void
    {
        Http::assertSent(fn($request) =>
            str_contains($request->url(), $endpoint)
        );
    }

    /**
     * Assert Slack notification job was dispatched
     */
    protected function assertSlackJobDispatched(string $jobClass): void
    {
        Queue::assertPushed($jobClass);
    }

    /**
     * Assert Slack notification was NOT dispatched
     */
    protected function assertSlackJobNotDispatched(string $jobClass): void
    {
        Queue::assertNotPushed($jobClass);
    }
}
