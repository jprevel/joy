<?php

namespace Database\Factories;

use App\Models\MagicLink;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MagicLinkFactory extends Factory
{
    protected $model = MagicLink::class;

    public function definition(): array
    {
        return [
            'token' => Str::random(64),
            'client_id' => Client::factory(),
            'scopes' => ['content.view', 'content.comment'],
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'accessed_at' => null,
            'pin' => null,
        ];
    }

    public function withPin(): static
    {
        return $this->state(fn (array $attributes) => [
            'pin_protected' => true,
            'pin' => bcrypt('123456'),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}