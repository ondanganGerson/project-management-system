<?php

namespace App\Services;

use App\Jobs\SendTaskAssignedNotificationJob;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * TaskAssignmentService
 *
 * Handles business logic for creating and assigning tasks to users.
 * Validates constraints such as: user existence, role eligibility,
 * project membership, and due-date boundaries.
 */
class TaskAssignmentService
{
    /**
     * Create a new task under a project and optionally assign it.
     *
     * @param  Project $project
     * @param  array   $data
     * @return Task
     * @throws ValidationException|\Throwable
     */
    public function createAndAssign(Project $project, array $data): Task
    {
        // Validate the assignee if provided
        if (! empty($data['assigned_to'])) {
            $this->validateAssignee((int) $data['assigned_to'], $project);
        }

        // Validate due date is within project range
        if (! empty($data['due_date'])) {
            $this->validateDueDate($data['due_date'], $project);
        }

        return DB::transaction(function () use ($project, $data) {
            $task = Task::create([
                'title'       => $data['title'],
                'description' => $data['description'] ?? null,
                'status'      => $data['status'] ?? Task::STATUS_PENDING,
                'due_date'    => $data['due_date'] ?? null,
                'project_id'  => $project->id,
                'assigned_to' => $data['assigned_to'] ?? null,
            ]);

            // Dispatch notification job if task has an assignee
            if ($task->assigned_to) {
                SendTaskAssignedNotificationJob::dispatch($task->load('assignee', 'project'));
            }

            return $task->load('assignee', 'project');
        });
    }

    /**
     * Update an existing task, re-validating assignment if it changes.
     *
     * @param  Task  $task
     * @param  array $data
     * @return Task
     * @throws ValidationException|\Throwable
     */
    public function updateTask(Task $task, array $data): Task
    {
        $project = $task->project;

        // Validate the new assignee if being changed
        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== null) {
            $this->validateAssignee((int) $data['assigned_to'], $project);
        }

        // Validate new due date
        if (! empty($data['due_date'])) {
            $this->validateDueDate($data['due_date'], $project);
        }

        return DB::transaction(function () use ($task, $data) {
            $previousAssignee = $task->assigned_to;

            $task->update($data);
            $task->refresh();

            // Notify new assignee if assignment changed
            if (
                array_key_exists('assigned_to', $data)
                && $data['assigned_to'] !== null
                && $data['assigned_to'] != $previousAssignee
            ) {
                SendTaskAssignedNotificationJob::dispatch($task->load('assignee', 'project'));
            }

            return $task->load('assignee', 'project');
        });
    }

    /**
     * Validate that the assignee exists and has an appropriate role.
     *
     * @param  int     $userId
     * @param  Project $project
     * @throws ValidationException
     */
    protected function validateAssignee(int $userId, Project $project): void
    {
        $user = User::find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'assigned_to' => ['The specified user does not exist.'],
            ]);
        }

        // Only non-admin users should be assigned tasks
        if ($user->isAdmin()) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Administrators cannot be assigned to tasks.'],
            ]);
        }
    }

    /**
     * Validate that the task due date falls within the project's date range.
     *
     * @param  string  $dueDate
     * @param  Project $project
     * @throws ValidationException
     */
    protected function validateDueDate(string $dueDate, Project $project): void
    {
        $due   = \Carbon\Carbon::parse($dueDate)->startOfDay();
        $start = $project->start_date->startOfDay();
        $end   = $project->end_date->startOfDay();

        if ($due->lt($start) || $due->gt($end)) {
            throw ValidationException::withMessages([
                'due_date' => [
                    "Task due date must be between the project start date ({$project->start_date->toDateString()}) "
                    . "and end date ({$project->end_date->toDateString()}).",
                ],
            ]);
        }
    }
}
