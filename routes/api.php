<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CommentController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Project Management System
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api (from bootstrap/app.php)
| and versioned under /v1.
|
| Role middleware: 'role:admin', 'role:manager', 'role:admin,manager'
|
*/

Route::prefix('v1')->group(function () {

    // ─── Auth Routes (Public) ─────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('/login',    [AuthController::class, 'login'])->name('auth.login');

        // Protected auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::get('/me',     [AuthController::class, 'me'])->name('auth.me');
        });
    });

    // ─── Protected Routes (Require Authentication) ────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // ── Project Routes ────────────────────────────────────────────────
        Route::prefix('projects')->group(function () {
            // All authenticated users can read projects
            Route::get('/',    [ProjectController::class, 'index'])->name('projects.index');
            Route::get('/{id}', [ProjectController::class, 'show'])->name('projects.show');

            // Admin-only: Create, Update, Delete
            Route::post('/',    [ProjectController::class, 'store'])->middleware('role:admin')->name('projects.store');
            Route::put('/{id}', [ProjectController::class, 'update'])->middleware('role:admin')->name('projects.update');
            Route::delete('/{id}', [ProjectController::class, 'destroy'])->middleware('role:admin')->name('projects.destroy');

            // Task Routes (nested under projects)
            Route::get('/{project_id}/tasks',  [TaskController::class, 'index'])->name('tasks.index');

            // Manager-only: create tasks under a project
            Route::post('/{project_id}/tasks', [TaskController::class, 'store'])
                ->middleware('role:admin,manager')
                ->name('tasks.store');
        });

        // ── Task Routes ───────────────────────────────────────────────────
        Route::prefix('tasks')->group(function () {
            // All authenticated users can view a task
            Route::get('/{id}', [TaskController::class, 'show'])->name('tasks.show');

            // Manager OR assigned user can update; controller handles fine-grained check
            Route::put('/{id}', [TaskController::class, 'update'])->name('tasks.update');

            // Manager-only: delete tasks
            Route::delete('/{id}', [TaskController::class, 'destroy'])
                ->middleware('role:admin,manager')
                ->name('tasks.destroy');

            // Comment Routes (nested under tasks)
            Route::get('/{task_id}/comments',  [CommentController::class, 'index'])->name('comments.index');
            Route::post('/{task_id}/comments', [CommentController::class, 'store'])->name('comments.store');
        });
    });
});

// ─── Fallback Route ───────────────────────────────────────────────────────
Route::fallback(function () {
    return response()->json([
        'status'  => 'error',
        'message' => 'API endpoint not found.',
        'data'    => null,
    ], 404);
});
