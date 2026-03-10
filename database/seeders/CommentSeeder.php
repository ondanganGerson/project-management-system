<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    public function run(): void
    {
        $tasks = Task::all();
        $users = User::all();

        $commentBodies = [
            'Initial setup complete. Moving to the next phase.',
            'Reviewed the requirements. Ready to start implementation.',
            'Encountered a blocker. Need clarification on the API specs.',
            'Blocker resolved. Resuming work on this task.',
            'Code review completed. Minor adjustments made.',
            'This is taking longer than expected. Updating the estimate.',
            'Good progress! Unit tests are all passing now.',
            'QA testing done. No major bugs found.',
            'Deployment to staging successful. Awaiting final approval.',
            'Task completed and verified in production.',
        ];

        foreach ($commentBodies as $index => $body) {
            $task = $tasks[$index % $tasks->count()];

            // Comments from either the assigned user or a manager
            $user = ($index % 3 === 0)
                ? User::where('role', 'manager')->inRandomOrder()->first()
                : User::where('id', $task->assigned_to)->first()
                  ?? User::inRandomOrder()->first();

            Comment::create([
                'body'    => $body,
                'task_id' => $task->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
