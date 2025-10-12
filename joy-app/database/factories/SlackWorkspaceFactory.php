<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackWorkspace>
 */
class SlackWorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => 'T' . fake()->unique()->regexify('[A-Z0-9]{10}'),
            'team_name' => fake()->company() . ' Workspace',
            'bot_token' => 'xoxb-' . fake()->regexify('[0-9]{12}-[0-9]{12}-[A-Za-z0-9]{24}'),
            'access_token' => fake()->boolean(30) ? 'xoxa-' . fake()->regexify('[0-9]{12}-[0-9]{12}-[A-Za-z0-9]{24}') : null,
            'scopes' => ['channels:read', 'groups:read', 'chat:write', 'chat:write.public'],
            'bot_user_id' => 'U' . fake()->regexify('[A-Z0-9]{10}'),
            'is_active' => true,
            'last_sync_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'last_error' => null,
            'metadata' => [
                'installed_by' => fake()->email(),
                'installation_date' => now()->toDateString(),
            ],
        ];
    }
}
