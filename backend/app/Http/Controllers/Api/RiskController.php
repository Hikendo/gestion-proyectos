<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Risk\StoreRiskRequest;
use App\Http\Requests\Risk\UpdateRiskRequest;
use App\Models\Project;
use App\Models\Risk;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RiskController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/risks
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $items = $project->risks()->latest()->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Riesgos encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/risks
     */
    public function store(StoreRiskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $item = $project->risks()->create($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Riesgo creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/risks/{risk}
     */
    public function show(Request $request, Project $project, Risk $risk): JsonResponse
    {
        $this->assertBelongsToProject($risk, $project->id);
        $this->authorize('view', $risk);

        return response()->json([
            'status'  => true,
            'items'   => $risk,
            'message' => 'Riesgo encontrado.',
        ]);
    }

    /**
     * PUT /api/projects/{project}/risks/{risk}
     */
    public function update(UpdateRiskRequest $request, Project $project, Risk $risk): JsonResponse
    {
        $this->assertBelongsToProject($risk, $project->id);
        $this->authorize('update', $risk);

        try {
            $risk->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $risk,
                'message' => 'Riesgo actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/risks/{risk}
     */
    public function destroy(Request $request, Project $project, Risk $risk): JsonResponse
    {
        $this->assertBelongsToProject($risk, $project->id);
        $this->authorize('delete', $risk);

        try {
            $risk->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Riesgo eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
