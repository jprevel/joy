<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\Illuminate\Database\Eloquent\Model>
     */
    protected $model = \App\Models\User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Create a new instance of the model with modified attributes.
     * We override this to intercept the role attribute.
     */
    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign a role to the user after creation using Spatie.
     */
    public function withRole(string $role): static
    {
        return $this->afterCreating(function (\App\Models\User $user) use ($role) {
            $user->assignRole($role);
        });
    }

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->withRole('admin');
    }

    /**
     * Create an agency user.
     */
    public function agency(): static
    {
        return $this->withRole('agency');
    }

    /**
     * Create a client user.
     */
    public function client(): static
    {
        return $this->withRole('client');
    }
}
