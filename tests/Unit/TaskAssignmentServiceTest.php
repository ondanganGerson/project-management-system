<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskAssignmentService $service;
    private Project $project;
    private User $admin;
    private User $manager;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->service = new TaskAssignmentService();

        $this->admin       = User::factory()->admin()->create();
        $this->manager     = User::factory()->manager()->create();
        $this->regularUser = User::factory()->user()->create();

        $this->project = Project::factory()->create([
            'created_by' => $this->admin->id,
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);
    }

    // ─── createAndAssign Tests ────────────────────────────────────────────

    /** @test */
    public function test_creates_task_without_assignee(): void
    {
        $task = $this->service->createAndAssign($this->project, [
            'title'  => 'Unassigned Task',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Unassigned Task', $task->title);
        $this->assertNull($task->assigned_to);
        $this->assertDatabaseHas('tasks', ['title' => 'Unassigned Task']);
    }

    /** @test */
    public function test_creates_task_with_valid_user_assignee(): void
    {
        $task = $this->service->createAndAssign($this->project, [
            'title'       => 'Assigned Task',
            'status'      => 'pending',
            'due_date'    => '2025-06-01',
            'assigned_to' => $this->regularUser->id,
        ]);

        $this->assertEquals($this->regularUser->id, $task->assigned_to);
        $this->assertDatabaseHas('tasks', [
            'title'       => 'Assigned Task',
            'assigned_to' => $this->regularUser->id,
        ]);
    }

    /** @test */
    public function test_creates_task_with_manager_as_assignee(): void
    {
        $task = $this->service->createAndAssign($this->project, [
            'title'       => 'Manager Task',
            'assigned_to' => $this->manager->id,
        ]);

        $this->assertEquals($this->manager->id, $task->assigned_to);
    }

    /** @test */
    public function test_throws_validation_exception_when_assigning_to_admin(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createAndAssign($this->project, [
            'title'       => 'Admin Task',
            'assigned_to' => $this->admin->id,
        ]);
    }

    /** @test */
    public function test_throws_validation_exception_for_nonexistent_assignee(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createAndAssign($this->project, [
            'title'       => 'Bad Assignee Task',
            'assigned_to' => 99999,
        ]);
    }

    /** @test */
    public function test_throws_validation_exception_for_due_date_before_project_start(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createAndAssign($this->project, [
            'title'    => 'Bad Date Task',
            'due_date' => '2024-12-31', // Before project start (2025-01-01)
        ]);
    }

    /** @test */
    public function test_throws_validation_exception_for_due_date_after_project_end(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->createAndAssign($this->project, [
            'title'    => 'Late Task',
            'due_date' => '2026-01-01', // After project end (2025-12-31)
        ]);
    }

    /** @test */
    public function test_accepts_due_date_at_project_boundaries(): void
    {
        // Start boundary
        $task1 = $this->service->createAndAssign($this->project, [
            'title'    => 'Start Boundary Task',
            'due_date' => '2025-01-01',
        ]);
        $this->assertEquals('2025-01-01', $task1->due_date->toDateString());

        // End boundary
        $task2 = $this->service->createAndAssign($this->project, [
            'title'    => 'End Boundary Task',
            'due_date' => '2025-12-31',
        ]);
        $this->assertEquals('2025-12-31', $task2->due_date->toDateString());
    }

    /** @test */
    public function test_defaults_status_to_pending_when_not_provided(): void
    {
        $task = $this->service->createAndAssign($this->project, [
            'title' => 'Task Without Status',
        ]);

        $this->assertEquals(Task::STATUS_PENDING, $task->status);
    }

    /** @test */
    public function test_dispatches_notification_job_when_assignee_set(): void
    {
        Queue::fake();

        $this->service->createAndAssign($this->project, [
            'title'       => 'Notification Task',
            'assigned_to' => $this->regularUser->id,
        ]);

        Queue::assertPushed(\App\Jobs\SendTaskAssignedNotificationJob::class);
    }

    /** @test */
    public function test_does_not_dispatch_notification_when_no_assignee(): void
    {
        Queue::fake();

        $this->service->createAndAssign($this->project, [
            'title' => 'No Assignee Task',
        ]);

        Queue::assertNotPushed(\App\Jobs\SendTaskAssignedNotificationJob::class);
    }

    // ─── updateTask Tests ─────────────────────────────────────────────────

    /** @test */
    public function test_updates_task_status(): void
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'status'     => 'pending',
        ]);

        $updated = $this->service->updateTask($task, ['status' => 'done']);

        $this->assertEquals('done', $updated->status);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
    }

    /** @test */
    public function test_throws_validation_exception_when_updating_to_admin_assignee(): void
    {
        $task = Task::factory()->create(['project_id' => $this->project->id]);

        $this->expectException(ValidationException::class);

        $this->service->updateTask($task, ['assigned_to' => $this->admin->id]);
    }

    /** @test */
    public function test_dispatches_notification_when_assignee_changes(): void
    {
        Queue::fake();

        $task = Task::factory()->create([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->regularUser->id,
        ]);

        $newUser = User::factory()->user()->create();
        $this->service->updateTask($task, ['assigned_to' => $newUser->id]);

        Queue::assertPushed(\App\Jobs\SendTaskAssignedNotificationJob::class);
    }
}
