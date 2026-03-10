<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-3 months', 'now');
        $endDate   = fake()->dateTimeBetween($startDate, '+6 months');

        return [
            'title'       => fake()->sentence(3),
            'description' => fake()->paragraph(2),
            'start_date'  => $startDate->format('Y-m-d'),
            'end_date'    => $endDate->format('Y-m-d'),
            'created_by'  => User::factory()->admin(),
        ];
    }
}
