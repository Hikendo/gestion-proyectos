<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Models\Project;
use App\Models\Task;
use App\Services\AttachmentService;
use App\Services\FieldPermissionsService;
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
        private ProjectService $projectService,
        private AttachmentService $attachmentService,
        private FieldPermissionsService $fieldPermissionsService,
    ) {}

    /**
     * GET /api/projects/{project}/tasks
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $query = $request->string('query', '')->trim()->toString();

            // Si no hay término de búsqueda, usamos Eloquent directamente porque
            // el driver "collection" de Scout devuelve vacío con search('').
            if ($query === '') {
                $items = Task::where('project_id', $project->id)
                    ->with(['assignee:id,name,email', 'phase:id,name'])
                    ->when($request->status,      fn($q, $s) => $q->where('status', $s))
                    ->when($request->assigned_to, fn($q, $u) => $q->where('assigned_to', $u))
                    ->when($request->priority,    fn($q, $p) => $q->where('priority', $p))
                    ->orderBy('due_date')
                    ->paginate(20);
            } else {
                $items = Task::search($query)
                    ->query(
                        fn($q) => $q
                            ->where('project_id', $project->id)
                            ->with(['assignee:id,name,email', 'phase:id,name'])
                            ->when($request->status,      fn($q, $s) => $q->where('status', $s))
                            ->when($request->assigned_to, fn($q, $u) => $q->where('assigned_to', $u))
                            ->when($request->priority,    fn($q, $p) => $q->where('priority', $p))
                            ->orderBy('due_date')
                    )
                    ->paginate(20);
            }

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Tareas encontradas.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/tasks
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $data = $request->validated();
            $files = $request->file('attachments', []);

            unset($data['attachments']);

            $item = $this->service->create($data, $project, $request->user());

            if (!empty($files)) {
                $this->attachmentService->uploadMany($item, $files, $request->user());
            }

            return response()->json([
                'status'  => true,
                'items'   => $item->load(['assignee:id,name,email', 'attachments']),
                'message' => 'Tarea creada.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/tasks/{task}
     */
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('view', $task);

        try {
            $task->load([
                'assignee:id,name,email',
                'creator:id,name,email',
                'phase:id,name',
                'comments.user:id,name,email',
                'timeLogs.user:id,name,email',
                'blockers',
                'attachments',
            ]);

            // Attach field-level permissions
            $task->field_permissions = $this->fieldPermissionsService->compute($request->user(), $task);

            return response()->json([
                'status'  => true,
                'items'   => $task,
                'message' => 'Tarea encontrada.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/projects/{project}/tasks/{task}
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('update', $task);

        try {
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

            return response()->json([
                'status'  => true,
                'items'   => $task->load('assignee:id,name,email'),
                'message' => 'Tarea actualizada.',
            ]);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'items' => null, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/tasks/{task}
     */
    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->assertBelongsToProject($task, $project->id);
        $this->authorize('delete', $task);

        try {
            $task->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Tarea eliminada.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/tasks/active
     * Returns tasks that are not completed (done) or cancelled, for use in selects.
     */
    public function active(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $items = Task::where('project_id', $project->id)
                ->where('status', '!=', TaskStatus::Done)
                ->orderBy('title')
                ->get(['id', 'title', 'status', 'priority']);

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Tareas activas encontradas.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/tasks/{task}/attachments
     *
     * Sube múltiples archivos adjuntos a una tarea existente.
     * Solo PM/owner pueden gestionar adjuntos de tareas.
     */
    public function uploadAttachments(Request $request, Task $task): JsonResponse
    {
        $this->authorize('manageAttachments', $task);

        $request->validate([
            'attachments'   => ['required', 'array'],
            'attachments.*' => ['file', 'max:102400'],
        ]);

        try {
            $files = $request->file('attachments');
            $uploaded = $this->attachmentService->uploadMany($task, $files, $request->user());

            return response()->json([
                'status'  => true,
                'data'    => $uploaded,
                'message' => count($uploaded) . ' archivo(s) subido(s) correctamente.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
