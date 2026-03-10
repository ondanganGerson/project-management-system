<?php

namespace App\Jobs;

use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SendTaskAssignedNotificationJob
 *
 * Queued job that dispatches the TaskAssignedNotification email
 * to the assigned user. Runs in the background via the queue worker.
 */
class SendTaskAssignedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    public function __construct(
        protected Task $task
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $task = $this->task->load('assignee', 'project');

        if (! $task->assignee) {
            Log::warning("SendTaskAssignedNotificationJob: Task #{$task->id} has no assignee. Skipping.");
            return;
        }

        Log::info("Sending task assignment notification", [
            'task_id'     => $task->id,
            'task_title'  => $task->title,
            'assigned_to' => $task->assignee->email,
        ]);

        $task->assignee->notify(new TaskAssignedNotification($task));
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendTaskAssignedNotificationJob failed for task #{$this->task->id}: " . $exception->getMessage());
    }
}
