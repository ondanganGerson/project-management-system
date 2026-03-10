<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * AuthController
 *
 * Handles user registration, login, logout, and profile retrieval
 * using Laravel Sanctum for token-based authentication.
 */
class AuthController extends Controller
{
    use ApiResponse;

    /**
     * POST /api/v1/auth/register
     *
     * Register a new user account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => $request->password, // auto-hashed via model cast
            'phone'    => $request->phone,
            'role'     => $request->role ?? User::ROLE_USER,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->createdResponse([
            'user'         => $this->userResource($user),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 'User registered successfully.');
    }

    /**
     * POST /api/v1/auth/login
     *
     * Authenticate a user and return an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Invalid credentials. Please check your email and password.', 401);
        }

        /** @var User $user */
        $user = Auth::user();

        // Revoke previous tokens to prevent token accumulation
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user'         => $this->userResource($user),
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 'Login successful.');
    }

    /**
     * POST /api/v1/auth/logout
     *
     * Invalidate the current access token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logged out successfully.');
    }

    /**
     * GET /api/v1/auth/me
     *
     * Return the authenticated user's profile.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->userResource($request->user()),
            'User profile retrieved.'
        );
    }

    /**
     * Format user data for API response.
     */
    private function userResource(User $user): array
    {
        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'role'       => $user->role,
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
        ];
    }
}
