<?php

namespace Database\Factories;

use App\Models\ContentItem;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContentItemFactory extends Factory
{
    protected $model = ContentItem::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->optional()->paragraph(),
            'platform' => $this->faker->randomElement(['facebook', 'instagram', 'linkedin', 'twitter', 'blog']),
            'status' => $this->faker->randomElement(['draft', 'review', 'approved', 'scheduled']),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('now', '+1 month'),
            'media_path' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'scheduled',
        ]);
    }
}