<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        $projects = [
            [
                'title'       => 'E-Commerce Platform Redesign',
                'description' => 'Complete redesign of the e-commerce platform with improved UX and performance.',
                'start_date'  => '2025-01-01',
                'end_date'    => '2025-06-30',
            ],
            [
                'title'       => 'Mobile App Development',
                'description' => 'Build a cross-platform mobile application for iOS and Android.',
                'start_date'  => '2025-02-01',
                'end_date'    => '2025-08-31',
            ],
            [
                'title'       => 'Data Analytics Dashboard',
                'description' => 'Develop a real-time analytics dashboard for business intelligence.',
                'start_date'  => '2025-03-01',
                'end_date'    => '2025-09-30',
            ],
            [
                'title'       => 'API Gateway Migration',
                'description' => 'Migrate legacy REST APIs to a modern API Gateway architecture.',
                'start_date'  => '2025-04-01',
                'end_date'    => '2025-10-31',
            ],
            [
                'title'       => 'Security Audit & Compliance',
                'description' => 'Conduct a full security audit and ensure regulatory compliance.',
                'start_date'  => '2025-05-01',
                'end_date'    => '2025-12-31',
            ],
        ];

        // Distribute projects among admins
        $admins = User::where('role', 'admin')->get();
        $adminIndex = 0;

        foreach ($projects as $projectData) {
            Project::create(array_merge($projectData, [
                'created_by' => $admins[$adminIndex % $admins->count()]->id,
            ]));
            $adminIndex++;
        }
    }
}
