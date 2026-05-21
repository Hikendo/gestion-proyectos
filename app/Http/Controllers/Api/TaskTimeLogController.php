<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TaskTimeLog\StoreTaskTimeLogRequest;
use App\Http\Resources\TaskTimeLogResource;
use App\Models\Task;
use Illuminate\Http\JsonResponse;

class TaskTimeLogController extends Controller
{
    /**
     * GET /api/tasks/{task}/time-logs
     */
    public function index(Task $task): JsonResponse
    {
        return TaskTimeLogResource::collection(
            $task->timeLogs()->with('user:id,name,email')->latest()->get()
        )->response();
    }

    /**
     * POST /api/tasks/{task}/time-logs
     */
    public function store(StoreTaskTimeLogRequest $request, Task $task): JsonResponse
    {
        $this->authorize('logTime', $task);

        $log = $task->timeLogs()->create([
            'user_id'     => $request->user()->id,
            'minutes'     => $request->validated('minutes'),
            'description' => $request->validated('description'),
        ]);

        $task->increment('worked_hours', round($request->validated('minutes') / 60, 2));

        return TaskTimeLogResource::make($log->load('user:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }
}
