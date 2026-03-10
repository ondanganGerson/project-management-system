<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private User $assignedUser;
    private User $unassignedUser;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin          = User::factory()->admin()->create();
        $this->manager        = User::factory()->manager()->create();
        $this->assignedUser   = User::factory()->user()->create();
        $this->unassignedUser = User::factory()->user()->create();

        $project = Project::factory()->create([
            'created_by' => $this->admin->id,
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);

        $this->task = Task::factory()->create([
            'project_id'  => $project->id,
            'assigned_to' => $this->assignedUser->id,
        ]);
    }

    private function token(User $user): string
    {
        return $user->createToken('test')->plainTextToken;
    }

    // ─── Index Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_list_comments_on_a_task(): void
    {
        Comment::factory()->count(3)->create([
            'task_id' => $this->task->id,
            'user_id' => $this->assignedUser->id,
        ]);

        $response = $this->withToken($this->token($this->assignedUser))
            ->getJson("/api/v1/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => ['comments', 'pagination']]);

        $this->assertCount(3, $response->json('data.comments'));
    }

    /** @test */
    public function test_returns_404_for_comments_on_nonexistent_task(): void
    {
        $response = $this->withToken($this->token($this->assignedUser))
            ->getJson('/api/v1/tasks/999/comments');

        $response->assertStatus(404);
    }

    // ─── Store Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_assigned_user_can_add_comment(): void
    {
        $response = $this->withToken($this->token($this->assignedUser))
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'This is a test comment.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'This is a test comment.')
            ->assertJsonPath('data.user.id', $this->assignedUser->id);

        $this->assertDatabaseHas('comments', [
            'body'    => 'This is a test comment.',
            'task_id' => $this->task->id,
            'user_id' => $this->assignedUser->id,
        ]);
    }

    /** @test */
    public function test_manager_can_add_comment_to_any_task(): void
    {
        $response = $this->withToken($this->token($this->manager))
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'Manager comment here.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Manager comment here.');
    }

    /** @test */
    public function test_admin_can_add_comment_to_any_task(): void
    {
        $response = $this->withToken($this->token($this->admin))
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'Admin comment.',
            ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function test_unassigned_user_cannot_add_comment(): void
    {
        $response = $this->withToken($this->token($this->unassignedUser))
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => 'Unauthorized comment.',
            ]);

        $response->assertStatus(403)
            ->assertJson(['status' => 'error']);
    }

    /** @test */
    public function test_comment_creation_fails_with_empty_body(): void
    {
        $response = $this->withToken($this->token($this->assignedUser))
            ->postJson("/api/v1/tasks/{$this->task->id}/comments", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_add_comment(): void
    {
        $response = $this->postJson("/api/v1/tasks/{$this->task->id}/comments", [
            'body' => 'Unauthenticated comment.',
        ]);

        $response->assertStatus(401);
    }
}
