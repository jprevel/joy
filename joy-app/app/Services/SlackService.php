<?php

namespace App\Services;

use App\Contracts\SlackServiceContract;
use App\Models\SlackWorkspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackService implements SlackServiceContract
{
    protected ?SlackWorkspace $workspace = null;
    protected string $baseUrl = 'https://slack.com/api/';

    /**
     * Set the workspace to use for API calls
     */
    public function setWorkspace(SlackWorkspace $workspace): self
    {
        $this->workspace = $workspace;
        return $this;
    }

    /**
     * Get the currently configured workspace
     */
    public function getWorkspace(): ?SlackWorkspace
    {
        return $this->workspace ?? SlackWorkspace::getDefault();
    }

    /**
     * Test connection to Slack API
     */
    public function testConnection(): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->post($this->baseUrl . 'auth.test');

            $data = $response->json();

            if (!($data['ok'] ?? false)) {
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'message' => 'Connection successful',
                'data' => $data,
            ];
        } catch (\Exception $e) {
            Log::error('Slack connection test failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get list of channels from Slack
     *
     * CRITICAL: Fetches both public AND private channels (clarification #3 from spec.md)
     * NO caching (clarification #5 from spec.md) - fetches live from API when needed
     */
    public function getChannels(bool $includeArchived = false, bool $includePrivate = true): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $channels = [];

            // Fetch public channels
            $publicResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'conversations.list', [
                'types' => 'public_channel',
                'exclude_archived' => !$includeArchived,
                'limit' => 200,
            ]);

            $publicData = $publicResponse->json();

            if ($publicData['ok'] ?? false) {
                $channels = array_merge($channels, $publicData['channels'] ?? []);
            }

            // Fetch private channels (requires groups:read scope)
            if ($includePrivate) {
                $privateResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $workspace->bot_token,
                ])->get($this->baseUrl . 'conversations.list', [
                    'types' => 'private_channel',
                    'exclude_archived' => !$includeArchived,
                    'limit' => 200,
                ]);

                $privateData = $privateResponse->json();

                if ($privateData['ok'] ?? false) {
                    $channels = array_merge($channels, $privateData['channels'] ?? []);
                }
            }

            return [
                'success' => true,
                'channels' => $channels,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch Slack channels', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get detailed information about a specific channel
     */
    public function getChannelInfo(string $channelId): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'conversations.info', [
                'channel' => $channelId,
            ]);

            $data = $response->json();

            if (!($data['ok'] ?? false)) {
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'channel' => $data['channel'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get channel info', [
                'channel_id' => $channelId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Post message to Slack channel
     */
    public function postMessage(string $channelId, array $blocks, ?string $text = null): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl . 'chat.postMessage', [
                'channel' => $channelId,
                'blocks' => $blocks,
                'text' => $text ?? 'New notification from Joy',
            ]);

            $data = $response->json();

            if (!($data['ok'] ?? false)) {
                Log::warning('Slack message failed', [
                    'channel' => $channelId,
                    'error' => $data['error'] ?? 'Unknown error',
                ]);
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'ts' => $data['ts'] ?? null,
                'channel' => $data['channel'] ?? $channelId,
            ];
        } catch (\Exception $e) {
            Log::error('Slack postMessage exception', [
                'channel' => $channelId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Validate that a channel exists and is accessible by the bot
     */
    public function channelExists(string $channelId): bool
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'conversations.info', [
                'channel' => $channelId,
            ]);

            $data = $response->json();

            return $data['ok'] ?? false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get workspace information (team name, team ID, etc.)
     */
    public function getWorkspaceInfo(): array
    {
        $workspace = $this->getWorkspace();

        if (!$workspace) {
            return ['success' => false, 'error' => 'No Slack workspace configured'];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $workspace->bot_token,
            ])->get($this->baseUrl . 'team.info');

            $data = $response->json();

            if (!($data['ok'] ?? false)) {
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error'];
            }

            return [
                'success' => true,
                'workspace' => $data['team'] ?? [],
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
