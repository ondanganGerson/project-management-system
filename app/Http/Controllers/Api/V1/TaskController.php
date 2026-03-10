<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskAssignmentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * TaskController
 *
 * Manages task CRUD operations within projects.
 * Role-based access:
 *   - GET (index/show): all authenticated users
 *   - POST (create):    manager only
 *   - PUT (update):     manager OR the assigned user
 *   - DELETE:           manager only
 */
class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected TaskAssignmentService $taskAssignmentService
    ) {}

    /**
     * GET /api/v1/projects/{project_id}/tasks
     *
     * List tasks for a project with optional status filter and title search.
     */
    public function index(Request $request, int $projectId): JsonResponse
    {
        $project = Project::find($projectId);

        if (! $project) {
            return $this->notFoundResponse('Project not found.');
        }

        $tasks = Task::with(['assignee:id,name,email', 'project:id,title'])
            ->where('project_id', $projectId)
            ->filterByStatus($request->string('status')->toString() ?: null)
            ->searchByTitle($request->string('search')->toString() ?: null)
            ->latestFirst()
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse([
            'tasks'      => $tasks->items(),
            'pagination' => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'per_page'     => $tasks->perPage(),
                'total'        => $tasks->total(),
            ],
        ], 'Tasks retrieved successfully.');
    }

    /**
     * GET /api/v1/tasks/{id}
     *
     * Show a single task with its project and assignee.
     */
    public function show(int $id): JsonResponse
    {
        $task = Task::with(['project:id,title,start_date,end_date', 'assignee:id,name,email', 'comments.user:id,name'])
            ->find($id);

        if (! $task) {
            return $this->notFoundResponse('Task not found.');
        }

        return $this->successResponse($task, 'Task retrieved successfully.');
    }

    /**
     * POST /api/v1/projects/{project_id}/tasks
     *
     * Create a new task in a project. Manager only.
     */
    public function store(StoreTaskRequest $request, int $projectId): JsonResponse
    {
        $project = Project::find($projectId);

        if (! $project) {
            return $this->notFoundResponse('Project not found.');
        }

        try {
            $task = $this->taskAssignmentService->createAndAssign($project, $request->validated());
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Task validation failed.');
        }

        return $this->createdResponse($task, 'Task created successfully.');
    }

    /**
     * PUT /api/v1/tasks/{id}
     *
     * Update a task. Manager OR the assigned user.
     */
    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $task = Task::with('project')->find($id);

        if (! $task) {
            return $this->notFoundResponse('Task not found.');
        }

        $user = $request->user();

        // Access control: manager or the user assigned to this task
        if (! $user->isManager() && ! $user->isAdmin() && $task->assigned_to !== $user->id) {
            return $this->forbiddenResponse('You can only update tasks assigned to you.');
        }

        // Regular users can only update status
        if ($user->isUser()) {
            $allowedFields = ['status'];
            $data = $request->only($allowedFields);
        } else {
            $data = $request->validated();
        }

        try {
            $task = $this->taskAssignmentService->updateTask($task, $data);
        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Task update validation failed.');
        }

        return $this->successResponse($task, 'Task updated successfully.');
    }

    /**
     * DELETE /api/v1/tasks/{id}
     *
     * Soft-delete a task. Manager only.
     */
    public function destroy(int $id): JsonResponse
    {
        $task = Task::find($id);

        if (! $task) {
            return $this->notFoundResponse('Task not found.');
        }

        $task->delete();

        return $this->successResponse(null, 'Task deleted successfully.');
    }
}
