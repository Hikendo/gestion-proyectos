<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcceptanceCriterion;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcceptanceCriterionController extends Controller
{
    /**
     * GET /api/projects/{project}/phases/{phase}/criteria
     */
    public function index(Request $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('view', $project);
        abort_if($phase->project_id !== $project->id, 404);

        try {
            $items = $phase->acceptanceCriteria()->orderBy('created_at')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Criterios de aceptación encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/phases/{phase}/criteria
     */
    public function store(Request $request, Project $project, ProjectPhase $phase): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $item = $phase->acceptanceCriteria()->create($validated);

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Criterio de aceptación creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/phases/{phase}/criteria/{criterion}
     */
    public function show(Request $request, Project $project, ProjectPhase $phase, AcceptanceCriterion $criterion): JsonResponse
    {
        $this->authorize('view', $project);
        abort_if($phase->project_id !== $project->id, 404);
        abort_if($criterion->phase_id !== $phase->id, 404);

        return response()->json([
            'status'  => true,
            'items'   => $criterion,
            'message' => 'Criterio de aceptación encontrado.',
        ]);
    }

    /**
     * PUT /api/projects/{project}/phases/{phase}/criteria/{criterion}
     * Solo permite marcar completed = true (no revertir).
     */
    public function update(Request $request, Project $project, ProjectPhase $phase, AcceptanceCriterion $criterion): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);
        abort_if($criterion->phase_id !== $phase->id, 404);

        $validated = $request->validate([
            'description' => ['sometimes', 'string', 'max:1000'],
            'completed'   => ['sometimes', 'boolean'],
        ]);

        try {
            $criterion->update($validated);

            return response()->json([
                'status'  => true,
                'items'   => $criterion,
                'message' => 'Criterio de aceptación actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/phases/{phase}/criteria/{criterion}
     */
    public function destroy(Request $request, Project $project, ProjectPhase $phase, AcceptanceCriterion $criterion): JsonResponse
    {
        $this->authorize('update', $project);
        abort_if($phase->project_id !== $project->id, 404);
        abort_if($criterion->phase_id !== $phase->id, 404);

        try {
            $criterion->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Criterio eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
