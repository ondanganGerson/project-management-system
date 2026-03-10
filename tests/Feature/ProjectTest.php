<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    private function adminToken(): array
    {
        $admin = User::factory()->admin()->create();
        return ['user' => $admin, 'token' => $admin->createToken('test')->plainTextToken];
    }

    private function managerToken(): array
    {
        $manager = User::factory()->manager()->create();
        return ['user' => $manager, 'token' => $manager->createToken('test')->plainTextToken];
    }

    private function userToken(): array
    {
        $user = User::factory()->user()->create();
        return ['user' => $user, 'token' => $user->createToken('test')->plainTextToken];
    }

    private function makeProject(array $overrides = []): Project
    {
        $admin = User::factory()->admin()->create();
        return Project::factory()->create(array_merge(['created_by' => $admin->id], $overrides));
    }

    // ─── Index Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_list_projects(): void
    {
        ['token' => $token] = $this->userToken();
        $this->makeProject();
        $this->makeProject();

        $response = $this->withToken($token)->getJson('/api/v1/projects');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => ['projects', 'pagination'],
            ]);
    }

    /** @test */
    public function test_unauthenticated_user_cannot_list_projects(): void
    {
        $response = $this->getJson('/api/v1/projects');
        $response->assertStatus(401);
    }

    /** @test */
    public function test_project_list_supports_title_search(): void
    {
        ['token' => $token] = $this->userToken();
        $admin = User::factory()->admin()->create();

        Project::factory()->create(['title' => 'Alpha Project', 'created_by' => $admin->id]);
        Project::factory()->create(['title' => 'Beta Project',  'created_by' => $admin->id]);

        $response = $this->withToken($token)->getJson('/api/v1/projects?search=Alpha');

        $response->assertStatus(200);
        $projects = $response->json('data.projects');
        $this->assertCount(1, $projects);
        $this->assertStringContainsStringIgnoringCase('Alpha', $projects[0]['title']);
    }

    // ─── Show Tests ───────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_view_a_project(): void
    {
        ['token' => $token] = $this->userToken();
        $project = $this->makeProject();

        $response = $this->withToken($token)->getJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.title', $project->title);
    }

    /** @test */
    public function test_returns_404_for_nonexistent_project(): void
    {
        ['token' => $token] = $this->userToken();

        $response = $this->withToken($token)->getJson('/api/v1/projects/999999');

        $response->assertStatus(404)->assertJson(['status' => 'error']);
    }

    // ─── Store Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_admin_can_create_project(): void
    {
        ['token' => $token] = $this->adminToken();

        $response = $this->withToken($token)->postJson('/api/v1/projects', [
            'title'       => 'New Project',
            'description' => 'A test project.',
            'start_date'  => '2025-01-01',
            'end_date'    => '2025-12-31',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'New Project')
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('projects', ['title' => 'New Project']);
    }

    /** @test */
    public function test_manager_cannot_create_project(): void
    {
        ['token' => $token] = $this->managerToken();

        $response = $this->withToken($token)->postJson('/api/v1/projects', [
            'title'      => 'Unauthorized Project',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_regular_user_cannot_create_project(): void
    {
        ['token' => $token] = $this->userToken();

        $response = $this->withToken($token)->postJson('/api/v1/projects', [
            'title'      => 'Unauthorized Project',
            'start_date' => '2025-01-01',
            'end_date'   => '2025-12-31',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function test_project_creation_fails_with_end_date_before_start_date(): void
    {
        ['token' => $token] = $this->adminToken();

        $response = $this->withToken($token)->postJson('/api/v1/projects', [
            'title'      => 'Bad Date Project',
            'start_date' => '2025-12-31',
            'end_date'   => '2025-01-01',
        ]);

        $response->assertStatus(422)->assertJson(['status' => 'error']);
    }

    // ─── Update Tests ─────────────────────────────────────────────────────

    /** @test */
    public function test_admin_can_update_project(): void
    {
        ['token' => $token] = $this->adminToken();
        $project = $this->makeProject();

        $response = $this->withToken($token)->putJson("/api/v1/projects/{$project->id}", [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.title', 'Updated Title');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'title' => 'Updated Title']);
    }

    /** @test */
    public function test_manager_cannot_update_project(): void
    {
        ['token' => $token] = $this->managerToken();
        $project = $this->makeProject();

        $response = $this->withToken($token)->putJson("/api/v1/projects/{$project->id}", [
            'title' => 'Hacked Title',
        ]);

        $response->assertStatus(403);
    }

    // ─── Delete Tests ─────────────────────────────────────────────────────

    /** @test */
    public function test_admin_can_delete_project(): void
    {
        ['token' => $token] = $this->adminToken();
        $project = $this->makeProject();

        $response = $this->withToken($token)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(200)->assertJson(['status' => 'success']);
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    /** @test */
    public function test_manager_cannot_delete_project(): void
    {
        ['token' => $token] = $this->managerToken();
        $project = $this->makeProject();

        $response = $this->withToken($token)->deleteJson("/api/v1/projects/{$project->id}");

        $response->assertStatus(403);
    }
}
