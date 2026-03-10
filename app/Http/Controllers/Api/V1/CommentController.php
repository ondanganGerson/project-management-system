<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Task;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * CommentController
 *
 * Manages comments on tasks.
 * - GET:  all authenticated users can view comments
 * - POST: authenticated users can add comments (they must be assigned to
 *         the task OR be a manager/admin)
 */
class CommentController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/v1/tasks/{task_id}/comments
     *
     * List all comments for a task, paginated.
     */
    public function index(Request $request, int $taskId): JsonResponse
    {
        $task = Task::find($taskId);

        if (! $task) {
            return $this->notFoundResponse('Task not found.');
        }

        $comments = Comment::with('user:id,name,email')
            ->where('task_id', $taskId)
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return $this->successResponse([
            'comments'   => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page'    => $comments->lastPage(),
                'per_page'     => $comments->perPage(),
                'total'        => $comments->total(),
            ],
        ], 'Comments retrieved successfully.');
    }

    /**
     * POST /api/v1/tasks/{task_id}/comments
     *
     * Add a comment to a task.
     * Regular users may only comment on tasks assigned to them.
     * Managers and Admins can comment on any task.
     */
    public function store(StoreCommentRequest $request, int $taskId): JsonResponse
    {
        $task = Task::find($taskId);

        if (! $task) {
            return $this->notFoundResponse('Task not found.');
        }

        $user = $request->user();

        // Regular users can only comment on tasks assigned to them
        if ($user->isUser() && $task->assigned_to !== $user->id) {
            return $this->forbiddenResponse('You can only comment on tasks assigned to you.');
        }

        $comment = Comment::create([
            'body'    => $request->body,
            'task_id' => $taskId,
            'user_id' => $user->id,
        ]);

        return $this->createdResponse(
            $comment->load('user:id,name,email'),
            'Comment added successfully.'
        );
    }
}
