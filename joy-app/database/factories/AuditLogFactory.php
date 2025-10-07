<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => null,
            'event' => $this->faker->randomElement([
                'user_login',
                'user_logout',
                'content_created',
                'content_updated',
                'content_deleted',
                'magic_link_accessed',
                'comment_created',
                'admin_action'
            ]),
            'auditable_type' => $this->faker->randomElement([
                'App\\Models\\User',
                'App\\Models\\ContentItem',
                'App\\Models\\Comment',
                'App\\Models\\MagicLink'
            ]),
            'auditable_id' => $this->faker->numberBetween(1, 100),
            'old_values' => $this->faker->optional()->passthrough([
                'status' => 'draft',
                'name' => $this->faker->words(2, true)
            ]),
            'new_values' => $this->faker->optional()->passthrough([
                'status' => 'published',
                'name' => $this->faker->words(2, true)
            ]),
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the audit log is for a specific user.
     */
    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Indicate that the audit log is for a system event (no user).
     */
    public function systemEvent(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the audit log is for a specific event type.
     */
    public function withEvent(string $event): static
    {
        return $this->state(fn (array $attributes) => [
            'event' => $event,
        ]);
    }

    /**
     * Indicate that the audit log has specific old/new values.
     */
    public function withChanges(array $oldValues, array $newValues): static
    {
        return $this->state(fn (array $attributes) => [
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }

    /**
     * Indicate that the audit log is for a recent time period.
     */
    public function recent(int $days = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => $this->faker->dateTimeBetween("-{$days} days", 'now'),
        ]);
    }
}