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
        try {
            $items = $task->comments()->with('user:id,name,email')->latest()->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Comentarios encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/tasks/{task}/comments
     */
    public function store(StoreTaskCommentRequest $request, Task $task): JsonResponse
    {
        try {
            $item = $task->comments()->create([
                'user_id' => $request->user()->id,
                'comment' => $request->validated('comment'),
            ]);

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Comentario creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
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

        try {
            $comment->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Comentario eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
