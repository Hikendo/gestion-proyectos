<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskComment\StoreTaskCommentRequest;
use App\Http\Resources\TaskCommentResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    /**
     * GET /api/tasks/{task}/comments
     */
    public function index(Task $task): JsonResponse
    {
        return TaskCommentResource::collection(
            $task->comments()->with('user:id,name,email')->latest()->get()
        )->response();
    }

    /**
     * POST /api/tasks/{task}/comments
     */
    public function store(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'comment' => $request->validated('comment'),
        ]);

        return TaskCommentResource::make($comment->load('user:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * DELETE /api/tasks/{task}/comments/{comment}
     */
    public function destroy(Request $request, Task $task, int $commentId): JsonResponse
    {
        $comment = $task->comments()->findOrFail($commentId);

        abort_if(
            $comment->user_id !== $request->user()->id
                && ! $request->user()->hasRole('super-admin'),
            403
        );

        $comment->delete();

        return response()->json(['message' => 'Comentario eliminado.']);
    }
}
