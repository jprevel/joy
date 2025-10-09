<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientStatusUpdate>
 */
class ClientStatusUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statusDate = fake()->dateTimeBetween('-30 days', 'now');
        $weekStart = Carbon::parse($statusDate)->startOfWeek(Carbon::SUNDAY);

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'status_notes' => fake()->paragraphs(2, true),
            'client_satisfaction' => fake()->numberBetween(1, 10),
            'team_health' => fake()->numberBetween(1, 10),
            'status_date' => $statusDate,
            'week_start_date' => $weekStart,
            'approval_status' => 'pending_approval',
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
