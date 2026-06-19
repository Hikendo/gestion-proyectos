<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskTimeLog\StoreTaskTimeLogRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class TaskTimeLogController extends Controller
{
    /**
     * GET /api/tasks/{task}/time-logs
     */
    public function index(Task $task): JsonResponse
    {
        try {
            $items = $task->timeLogs()->with('user:id,name,email')->latest()->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Registros de tiempo encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/tasks/{task}/time-logs
     */
    public function store(StoreTaskTimeLogRequest $request, Task $task): JsonResponse
    {
        $this->authorize('logTime', $task);

        // No se pueden registrar horas en tareas completadas
        if ($task->status === TaskStatus::Done) {
            return response()->json([
                'status'  => false,
                'items'   => null,
                'message' => 'No se pueden registrar horas en una tarea completada.',
            ], 422);
        }

        try {
            $item = $task->timeLogs()->create([
                'user_id'     => $request->user()->id,
                'minutes'     => $request->validated('minutes'),
                'description' => $request->validated('description'),
            ]);

            $task->increment('worked_hours', round($request->validated('minutes') / 60, 2));

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Registro de tiempo creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
