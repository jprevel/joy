<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\ContentItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'content_item_id' => ContentItem::factory(),
            'user_id' => User::factory(),
            'body' => $this->faker->paragraph(),
            'visibility' => $this->faker->randomElement(['public', 'private', 'internal']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}