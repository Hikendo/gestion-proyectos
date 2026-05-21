<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BlockerException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Blocker\StoreBlockerRequest;
use App\Http\Requests\Blocker\UpdateBlockerRequest;
use App\Http\Resources\BlockerResource;
use App\Models\Blocker;
use App\Models\Project;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BlockerController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/blockers
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return BlockerResource::collection(
            $project->blockers()
                ->with('task:id,title')
                ->when(
                    ! $request->boolean('include_resolved'),
                    fn($q) => $q->where('resolved', false)
                )
                ->latest()
                ->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/blockers
     */
    public function store(StoreBlockerRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $blocker = $project->blockers()->create($request->validated());

        return BlockerResource::make($blocker)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/blockers/{blocker}
     */
    public function update(UpdateBlockerRequest $request, Project $project, Blocker $blocker): JsonResponse
    {
        $this->assertBelongsToProject($blocker, $project->id);
        $this->authorize('update', $blocker);

        $blocker->update($request->validated());

        return BlockerResource::make($blocker)->response();
    }

    /**
     * PATCH /api/projects/{project}/blockers/{blocker}/resolve
     */
    public function resolve(Request $request, Project $project, Blocker $blocker): JsonResponse
    {
        // Chequear con el mismo campo que usa la policy ($blocker->resolved)
        if ($blocker->resolved) {
            return response()->json(['message' => 'El blocker ya fue resuelto.'], 422);
        }

        $this->authorize('resolve', $blocker);

        $blocker->update([
            'resolved'    => true,        // ← campo que usa la policy y el test
            'resolved_at' => now(),       // ← campo de auditoría
            'resolved_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $blocker]);
    }
}
