<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ─── Registration Tests ───────────────────────────────────────────────

    /** @test */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'phone'                 => '+1-555-0100',
            'role'                  => 'user',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'phone', 'role'],
                    'access_token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'status'  => 'success',
                'message' => 'User registered successfully.',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role'  => 'user',
        ]);
    }

    /** @test */
    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error'])
            ->assertJsonPath('errors.email.0', 'This email address is already registered.');
    }

    /** @test */
    public function test_registration_fails_with_password_mismatch(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password confirmation does not match.');
    }

    /** @test */
    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.password.0', 'Password must be at least 8 characters.');
    }

    /** @test */
    public function test_registration_fails_with_invalid_role(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'John Doe',
            'email'                 => 'john@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'superadmin',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('errors.role.0', 'Role must be one of: admin, manager, user.');
    }

    // ─── Login Tests ──────────────────────────────────────────────────────

    /** @test */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => ['user', 'access_token', 'token_type'],
            ])
            ->assertJson([
                'status'  => 'success',
                'message' => 'Login successful.',
            ]);
    }

    /** @test */
    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    /** @test */
    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['status' => 'error']);
    }

    /** @test */
    public function test_login_fails_with_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJson(['status' => 'error']);
    }

    // ─── Logout Tests ─────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status'  => 'success',
                'message' => 'Logged out successfully.',
            ]);

        // Token should be deleted
        $this->assertDatabaseEmpty('personal_access_tokens');
    }

    /** @test */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    // ─── Me Tests ─────────────────────────────────────────────────────────

    /** @test */
    public function test_authenticated_user_can_get_their_profile(): void
    {
        $user  = User::factory()->create(['name' => 'Jane Doe', 'role' => 'manager']);
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Jane Doe')
            ->assertJsonPath('data.role', 'manager');
    }

    /** @test */
    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }
}
