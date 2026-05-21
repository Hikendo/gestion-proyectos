<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectPhase\StoreProjectPhaseRequest;
use App\Http\Requests\ProjectPhase\UpdateProjectPhaseRequest;
use App\Http\Resources\ProjectPhaseResource;
use App\Models\Project;
use App\Models\ProjectPhase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectPhaseController extends Controller
{
    /**
     * GET /api/projects/{project}/phases
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ProjectPhaseResource::collection(
            $project->phases()->withCount('tasks')->orderBy('start_date')->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/phases
     */
    public function store(StoreProjectPhaseRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $phase = $project->phases()->create($request->validated());

        return ProjectPhaseResource::make($phase)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/phases/{phase}
     */
    public function update(UpdateProjectPhaseRequest $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);

        $phase->update($request->validated());

        return ProjectPhaseResource::make($phase)->response();
    }

    /**
     * DELETE /api/projects/{project}/phases/{phase}
     */
    public function destroy(Request $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);

        $phase->delete();

        return response()->json(['message' => 'Fase eliminada.']);
    }
}
