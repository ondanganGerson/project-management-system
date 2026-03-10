<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $regularUser;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Notification::fake();

        $this->admin   = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
        $this->regularUser = User::factory()->user()->create();

        $this->project = Project::factory()->create([
            'created_by' => $this->admin->id,
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    private function makeTask(array $overrides = []): Task
    {
        return Task::factory()->create(array_merge([
            'project_id'  => $this->project->id,
            'assigned_to' => $this->regularUser->id,
        ], $overrides));
    }

    // ─── Index Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_list_project_tasks(): void
    {
        $this->makeTask();
        $this->makeTask();

        $response = $this->withToken($this->token($this->regularUser))
            ->getJson("/api/v1/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['tasks', 'pagination']]);
    }

    /** @test */
    public function test_task_list_can_filter_by_status(): void
    {
        $this->makeTask(['status' => 'pending']);
        $this->makeTask(['status' => 'done']);

        $response = $this->withToken($this->token($this->regularUser))
            ->getJson("/api/v1/projects/{$this->project->id}/tasks?status=pending");

        $response->assertStatus(200);
        $tasks = $response->json('data.tasks');
        $this->assertCount(1, $tasks);
        $this->assertEquals('pending', $tasks[0]['status']);
    }

    /** @test */
    public function test_returns_404_for_tasks_under_nonexistent_project(): void
    {
        $response = $this->withToken($this->token($this->regularUser))
            ->getJson('/api/v1/projects/999/tasks');

        $response->assertStatus(404);
    }

    // ─── Show Tests ───────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_view_a_task(): void
    {
        $task = $this->makeTask();

        $response = $this->withToken($this->token($this->regularUser))
            ->getJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $task->id);
    }

    // ─── Store Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_manager_can_create_task(): void
    {
        $response = $this->withToken($this->token($this->manager))
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'       => 'New Task',
                'description' => 'A test task',
                'status'      => 'pending',
                'due_date'    => '2025-06-01',
                'assigned_to' => $this->regularUser->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Task');

        $this->assertDatabaseHas('tasks', ['title' => 'New Task']);
    }

    /** @test */
    public function test_admin_can_create_task(): void
    {
        $response = $this->withToken($this->token($this->admin))
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'    => 'Admin Created Task',
                'due_date' => '2025-06-01',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_regular_user_cannot_create_task(): void
    {
        $response = $this->withToken($this->token($this->regularUser))
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'    => 'Unauthorized Task',
                'due_date' => '2025-06-01',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_task_creation_fails_with_nonexistent_assignee(): void
    {
        $response = $this->withToken($this->token($this->manager))
            ->postJson("/api/v1/projects/{$this->project->id}/tasks", [
                'title'       => 'Task with Bad Assignee',
                'assigned_to' => 99999,
            ]);

        $response->assertStatus(422);
    }

    // ─── Update Tests ─────────────────────────────────────────────────────

    /** @test */
    public function test_manager_can_update_any_task(): void
    {
        $task = $this->makeTask(['status' => 'pending']);

        $response = $this->withToken($this->token($this->manager))
            ->putJson("/api/v1/tasks/{$task->id}", [
                'status' => 'done',
                'title'  => 'Updated Task Title',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'done');
    }

    /** @test */
    public function test_assigned_user_can_update_their_own_task_status(): void
    {
        $task = $this->makeTask([
            'assigned_to' => $this->regularUser->id,
            'status'      => 'pending',
        ]);

        $response = $this->withToken($this->token($this->regularUser))
            ->putJson("/api/v1/tasks/{$task->id}", [
                'status' => 'in-progress',
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function test_unassigned_user_cannot_update_a_task(): void
    {
        $otherUser = User::factory()->user()->create();
        $task = $this->makeTask(['assigned_to' => $this->regularUser->id]);

        $response = $this->withToken($this->token($otherUser))
            ->putJson("/api/v1/tasks/{$task->id}", ['status' => 'done']);

        $response->assertStatus(403);
    }

    // ─── Delete Tests ─────────────────────────────────────────────────────

    /** @test */
    public function test_manager_can_delete_task(): void
    {
        $task = $this->makeTask();

        $response = $this->withToken($this->token($this->manager))
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function test_regular_user_cannot_delete_task(): void
    {
        $task = $this->makeTask(['assigned_to' => $this->regularUser->id]);

        $response = $this->withToken($this->token($this->regularUser))
            ->deleteJson("/api/v1/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}
