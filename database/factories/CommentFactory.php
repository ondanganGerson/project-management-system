<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'body'    => fake()->paragraph(),
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
        ];
    }
}
