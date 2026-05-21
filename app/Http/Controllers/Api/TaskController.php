<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectService;
use App\Services\TaskService;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    use BelongsToProject;

    public function __construct(
        private TaskService $service,
        private ProjectService $projectService
    ) {}

    /**
     * GET /api/projects/{project}/tasks
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tasks = $project->tasks()
            ->with(['assignee:id,name,email', 'phase:id,name'])
            ->when($request->status,      fn($q, $s) => $q->where('status', $s))
            ->when($request->assigned_to, fn($q, $u) => $q->where('assigned_to', $u))
            ->when($request->priority,    fn($q, $p) => $q->where('priority', $p))
            ->orderBy('due_date')
            ->paginate(20);

        return TaskResource::collection($tasks)->response();
    }

    /**
     * POST /api/projects/{project}/tasks
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $task = $this->service->create($request->validated(), $project, $request->user());

        return TaskResource::make($task->load('assignee:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/projects/{project}/tasks/{task}
     */
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('view', $task);

        $task->load([
            'assignee:id,name,email',
            'creator:id,name,email',
            'phase:id,name',
            'comments.user:id,name,email',
            'timeLogs.user:id,name,email',
            'blockers',
        ]);

        return TaskResource::make($task)->response();
    }

    /**
     * PUT /api/projects/{project}/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('update', $task);

        $data = $request->validated();

        if (isset($data['status'])) {
            $this->authorize('updateStatus', $task);
            $this->service->changeStatus(
                $task,
                TaskStatus::from($data['status']),
                $request->user()
            );
            unset($data['status']);
        }

        if (isset($data['assigned_to'])) {
            $this->authorize('assign', $task);
        }

        if (! empty($data)) {
            $task->update($data);
        }

        return TaskResource::make($task->load('assignee:id,name,email'))->response();
    }

    /**
     * DELETE /api/projects/{project}/tasks/{task}
     */
    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Tarea eliminada.']);
    }
}
