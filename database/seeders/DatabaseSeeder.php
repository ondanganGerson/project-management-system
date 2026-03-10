<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Seeded data:
     *   - 3 admin users
     *   - 3 manager users
     *   - 5 regular users
     *   - 5 projects (created by admins)
     *   - 10 tasks (spread across projects, assigned to users/managers)
     *   - 10 comments (spread across tasks)
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            ProjectSeeder::class,
            TaskSeeder::class,
            CommentSeeder::class,
        ]);

        $this->command->info('✅ Database seeded successfully!');
        $this->command->line('');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin',   'admin1@pms.local',   'password'],
                ['Admin',   'admin2@pms.local',   'password'],
                ['Admin',   'admin3@pms.local',   'password'],
                ['Manager', 'manager1@pms.local', 'password'],
                ['Manager', 'manager2@pms.local', 'password'],
                ['Manager', 'manager3@pms.local', 'password'],
                ['User',    'user1@pms.local',    'password'],
                ['User',    'user2@pms.local',    'password'],
                ['User',    'user3@pms.local',    'password'],
                ['User',    'user4@pms.local',    'password'],
                ['User',    'user5@pms.local',    'password'],
            ]
        );
    }
}
