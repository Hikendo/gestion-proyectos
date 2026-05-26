<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectPhase\StoreProjectPhaseRequest;
use App\Http\Requests\ProjectPhase\UpdateProjectPhaseRequest;
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

        try {
            $items = $project->phases()->withCount('tasks')->orderBy('start_date')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Fases encontradas.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/phases
     */
    public function store(StoreProjectPhaseRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        try {
            $item = $project->phases()->create($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Fase creada.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/projects/{project}/phases/{phase}
     */
    public function update(UpdateProjectPhaseRequest $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);

        try {
            $phase->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $phase,
                'message' => 'Fase actualizada.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/phases/{phase}
     */
    public function destroy(Request $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);

        try {
            $phase->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Fase eliminada.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
