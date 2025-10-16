<?php

namespace App\Contracts;

use App\Models\SlackWorkspace;

/**
 * Interface SlackServiceContract
 *
 * Defines the contract for interacting with the Slack Web API.
 * This service handles low-level Slack operations like posting messages,
 * fetching channels, and managing workspace connections.
 *
 * @package App\Contracts
 */
interface SlackServiceContract
{
    /**
     * Test the connection to Slack workspace using configured credentials.
     *
     * @return array{success: bool, message: string, data?: array, error?: string}
     */
    public function testConnection(): array;

    /**
     * Fetch list of channels from the connected Slack workspace.
     *
     * @param bool $includeArchived Whether to include archived channels
     * @param bool $includePrivate Whether to include private channels
     * @return array{success: bool, channels?: array, error?: string}
     */
    public function getChannels(bool $includeArchived = false, bool $includePrivate = false): array;

    /**
     * Get detailed information about a specific channel.
     *
     * @param string $channelId Slack channel ID
     * @return array{success: bool, channel?: array, error?: string}
     */
    public function getChannelInfo(string $channelId): array;

    /**
     * Post a message to a Slack channel using Block Kit format.
     *
     * @param string $channelId Slack channel ID
     * @param array $blocks Array of Slack Block Kit blocks
     * @param string|null $text Fallback plain text message
     * @return array{success: bool, ts?: string, error?: string}
     */
    public function postMessage(string $channelId, array $blocks, ?string $text = null): array;

    /**
     * Validate that a channel exists and is accessible by the bot.
     *
     * @param string $channelId Slack channel ID
     * @return bool
     */
    public function channelExists(string $channelId): bool;

    /**
     * Get workspace information (team name, team ID, etc.).
     *
     * @return array{success: bool, workspace?: array, error?: string}
     */
    public function getWorkspaceInfo(): array;

    /**
     * Set the workspace to use for API calls.
     *
     * @param SlackWorkspace $workspace
     * @return self
     */
    public function setWorkspace(SlackWorkspace $workspace): self;

    /**
     * Get the currently configured workspace.
     *
     * @return SlackWorkspace|null
     */
    public function getWorkspace(): ?SlackWorkspace;
}
