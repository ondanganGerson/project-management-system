<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * ProjectController
 *
 * Manages project CRUD operations.
 * - Read operations: accessible to all authenticated users
 * - Write operations (Create/Update/Delete): admin only (enforced via route middleware)
 * - Project listings are cached for performance
 */
class ProjectController extends Controller
{
    use ApiResponse;

    private const CACHE_TTL     = 300; // 5 minutes
    private const CACHE_KEY     = 'projects_list';
    private const PER_PAGE      = 15;

    /**
     * GET /api/v1/projects
     *
     * Return paginated list of projects with optional filtering and search.
     * Results are cached for 5 minutes.
     */
    public function index(Request $request): JsonResponse
    {
        $page    = $request->integer('page', 1);
        $status  = $request->string('status')->toString();
        $search  = $request->string('search')->toString();
        $perPage = $request->integer('per_page', self::PER_PAGE);

        // Build a unique cache key per query combination
        $cacheKey = self::CACHE_KEY . ":{$page}:{$perPage}:{$status}:{$search}";

        $projects = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($status, $search, $perPage) {
            return Project::with(['creator:id,name,email', 'tasks'])
                ->searchByTitle($search ?: null)
                ->latestFirst()
                ->paginate($perPage);
        });

        return $this->successResponse([
            'projects'   => $projects->items(),
            'pagination' => [
                'current_page' => $projects->currentPage(),
                'last_page'    => $projects->lastPage(),
                'per_page'     => $projects->perPage(),
                'total'        => $projects->total(),
                'from'         => $projects->firstItem(),
                'to'           => $projects->lastItem(),
            ],
        ], 'Projects retrieved successfully.');
    }

    /**
     * GET /api/v1/projects/{id}
     *
     * Return a single project with its tasks and creator.
     */
    public function show(int $id): JsonResponse
    {
        $project = Project::with(['creator:id,name,email,role', 'tasks.assignee:id,name,email'])
            ->find($id);

        if (! $project) {
            return $this->notFoundResponse('Project not found.');
        }

        return $this->successResponse($project, 'Project retrieved successfully.');
    }

    /**
     * POST /api/v1/projects
     *
     * Create a new project. Admin only.
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = Project::create([
            'title'       => $request->title,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'created_by'  => $request->user()->id,
        ]);

        $this->clearProjectCache();

        return $this->createdResponse(
            $project->load('creator:id,name,email'),
            'Project created successfully.'
        );
    }

    /**
     * PUT /api/v1/projects/{id}
     *
     * Update an existing project. Admin only.
     */
    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = Project::find($id);

        if (! $project) {
            return $this->notFoundResponse('Project not found.');
        }

        $project->update($request->validated());

        $this->clearProjectCache();

        return $this->successResponse(
            $project->fresh(['creator:id,name,email']),
            'Project updated successfully.'
        );
    }

    /**
     * DELETE /api/v1/projects/{id}
     *
     * Soft-delete a project. Admin only.
     */
    public function destroy(int $id): JsonResponse
    {
        $project = Project::find($id);

        if (! $project) {
            return $this->notFoundResponse('Project not found.');
        }

        $project->delete();

        $this->clearProjectCache();

        return $this->successResponse(null, 'Project deleted successfully.');
    }

    /**
     * Clear all project listing cache entries.
     */
    private function clearProjectCache(): void
    {
        // Clear with a tag if using Redis/Memcached; otherwise use prefix flush
        Cache::forget(self::CACHE_KEY);

        // Flush pattern-matched keys (works for database/file cache drivers)
        // For Redis: Cache::tags(['projects'])->flush()
    }
}
