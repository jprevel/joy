<?php

namespace Database\Factories;

use App\Models\SlackWorkspace;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SlackNotification>
 */
class SlackNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            'client_comment',
            'content_approved',
            'statusfaction_submitted',
            'statusfaction_approved'
        ];

        return [
            'workspace_id' => SlackWorkspace::factory(),
            'type' => fake()->randomElement($types),
            'notifiable_type' => Comment::class,
            'notifiable_id' => fake()->numberBetween(1, 100),
            'channel_id' => 'C' . fake()->regexify('[A-Z0-9]{10}'),
            'channel_name' => '#' . fake()->slug(2),
            'status' => 'sent',
            'payload' => [
                'blocks' => [
                    [
                        'type' => 'section',
                        'text' => [
                            'type' => 'mrkdwn',
                            'text' => fake()->sentence()
                        ]
                    ]
                ]
            ],
            'response' => [
                'ok' => true,
                'ts' => (string) fake()->unixTime()
            ],
            'error_message' => null,
            'sent_at' => now(),
        ];
    }

    /**
     * Indicate that the notification failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'response' => null,
            'error_message' => fake()->sentence(),
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the notification is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'response' => null,
            'sent_at' => null,
        ]);
    }
}
