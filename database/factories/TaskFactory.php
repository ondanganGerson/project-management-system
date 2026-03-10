<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status'      => fake()->randomElement(Task::STATUSES),
            'due_date'    => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'project_id'  => Project::factory(),
            'assigned_to' => User::factory()->user(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => Task::STATUS_PENDING]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => ['status' => Task::STATUS_IN_PROGRESS]);
    }

    public function done(): static
    {
        return $this->state(fn () => ['status' => Task::STATUS_DONE]);
    }

    public function unassigned(): static
    {
        return $this->state(fn () => ['assigned_to' => null]);
    }
}
