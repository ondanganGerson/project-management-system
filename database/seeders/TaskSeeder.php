<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::all();
        $users    = User::where('role', 'user')->get();
        $managers = User::where('role', 'manager')->get();

        $tasks = [
            [
                'title'       => 'Setup CI/CD Pipeline',
                'description' => 'Configure GitHub Actions for automated testing and deployment.',
                'status'      => 'done',
                'due_date'    => '2025-02-15',
            ],
            [
                'title'       => 'Design Database Schema',
                'description' => 'Create an optimized database schema with proper indexing.',
                'status'      => 'done',
                'due_date'    => '2025-02-28',
            ],
            [
                'title'       => 'Implement Authentication Module',
                'description' => 'Build JWT-based authentication with refresh tokens.',
                'status'      => 'in-progress',
                'due_date'    => '2025-03-15',
            ],
            [
                'title'       => 'Build Product Listing API',
                'description' => 'Develop REST endpoints for product listing with filters and pagination.',
                'status'      => 'in-progress',
                'due_date'    => '2025-04-01',
            ],
            [
                'title'       => 'Write Unit Tests',
                'description' => 'Write PHPUnit tests with at least 85% code coverage.',
                'status'      => 'pending',
                'due_date'    => '2025-04-30',
            ],
            [
                'title'       => 'Performance Optimization',
                'description' => 'Optimize database queries and implement caching strategies.',
                'status'      => 'pending',
                'due_date'    => '2025-05-15',
            ],
            [
                'title'       => 'UI Component Library',
                'description' => 'Build a reusable React component library following design system.',
                'status'      => 'in-progress',
                'due_date'    => '2025-05-30',
            ],
            [
                'title'       => 'Payment Gateway Integration',
                'description' => 'Integrate Stripe payment gateway with webhook handling.',
                'status'      => 'pending',
                'due_date'    => '2025-06-15',
            ],
            [
                'title'       => 'Security Penetration Testing',
                'description' => 'Conduct OWASP-based security testing across all endpoints.',
                'status'      => 'pending',
                'due_date'    => '2025-07-01',
            ],
            [
                'title'       => 'Documentation & API Reference',
                'description' => 'Write comprehensive API documentation using OpenAPI 3.0.',
                'status'      => 'pending',
                'due_date'    => '2025-07-30',
            ],
        ];

        foreach ($tasks as $index => $taskData) {
            // Distribute tasks across projects (2 per project)
            $project = $projects[$index % $projects->count()];

            // Alternately assign to users and managers
            $assignee = ($index % 2 === 0)
                ? $users[$index % $users->count()]
                : $managers[$index % $managers->count()];

            Task::create(array_merge($taskData, [
                'project_id'  => $project->id,
                'assigned_to' => $assignee->id,
            ]));
        }
    }
}
