<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Blocker\StoreBlockerRequest;
use App\Http\Requests\Blocker\UpdateBlockerRequest;
use App\Models\Blocker;
use App\Models\Project;
use App\Services\AttachmentService;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockerController extends Controller
{
    use BelongsToProject;

    public function __construct(
        private AttachmentService $attachmentService,
    ) {}

    /**
     * GET /api/projects/{project}/blockers
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $items = $project->blockers()
                ->with('task:id,title')
                ->when(
                    ! $request->boolean('include_resolved'),
                    fn($q) => $q->where('resolved', false)
                )
                ->latest()
                ->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Blockers encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/blockers
     */
    public function store(StoreBlockerRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $data = $request->validated();
            $files = $request->file('attachments', []);

            unset($data['attachments']);

            $item = $project->blockers()->create($data);

            if (!empty($files)) {
                $this->attachmentService->uploadMany($item, $files, $request->user());
            }

            return response()->json([
                'status'  => true,
                'items'   => $item->load(['attachments']),
                'message' => 'Blocker creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/blockers/{blocker}
     */
    public function show(Request $request, Project $project, Blocker $blocker): JsonResponse
    {
        $this->assertBelongsToProject($blocker, $project->id);
        $this->authorize('view', $blocker);

        return response()->json([
            'status'  => true,
            'items'   => $blocker->load(['task:id,title', 'createdBy:id,name', 'attachments']),
            'message' => 'Blocker encontrado.',
        ]);
    }

    /**
     * PUT /api/projects/{project}/blockers/{blocker}
     */
    public function update(UpdateBlockerRequest $request, Project $project, Blocker $blocker): JsonResponse
    {
        $this->assertBelongsToProject($blocker, $project->id);
        $this->authorize('update', $blocker);

        try {
            $blocker->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $blocker,
                'message' => 'Blocker actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/projects/{project}/blockers/{blocker}/resolve
     */
    public function resolve(Request $request, Project $project, Blocker $blocker): JsonResponse
    {
        // Chequear con el mismo campo que usa la policy ($blocker->resolved)
        if ($blocker->resolved) {
            return response()->json(['status' => false, 'items' => null, 'message' => 'El blocker ya fue resuelto.'], 422);
        }

        $this->authorize('resolve', $blocker);

        try {
            $blocker->update([
                'resolved'    => true,
                'resolved_at' => now(),
                'resolved_by' => $request->user()->id,
            ]);

            return response()->json([
                'status'  => true,
                'items'   => $blocker,
                'message' => 'Blocker resuelto.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/projects/{project}/blockers/{blocker}/attachments
     *
     * Solo PM/owner pueden gestionar adjuntos de blockers.
     */
    public function uploadAttachments(Request $request, Project $project, Blocker $blocker): JsonResponse
    {
        $this->assertBelongsToProject($blocker, $project->id);
        $this->authorize('update', $project);

        $request->validate([
            'attachments'   => ['required', 'array'],
            'attachments.*' => ['file', 'max:102400'],
        ]);

        try {
            $files = $request->file('attachments');
            $uploaded = $this->attachmentService->uploadMany($blocker, $files, $request->user());

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
