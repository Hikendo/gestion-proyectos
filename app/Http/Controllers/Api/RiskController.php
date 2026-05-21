<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Risk\StoreRiskRequest;
use App\Http\Requests\Risk\UpdateRiskRequest;
use App\Http\Resources\RiskResource;
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

        return RiskResource::collection(
            $project->risks()->latest()->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/risks
     */
    public function store(StoreRiskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $risk = $project->risks()->create($request->validated());

        return RiskResource::make($risk)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/risks/{risk}
     */
    public function update(UpdateRiskRequest $request, Project $project, Risk $risk): JsonResponse
    {
        $this->assertBelongsToProject($risk, $project->id);
        $this->authorize('update', $risk);

        $risk->update($request->validated());

        return RiskResource::make($risk)->response();
    }

    /**
     * DELETE /api/projects/{project}/risks/{risk}
     */
    public function destroy(Request $request, Project $project, Risk $risk): JsonResponse
    {
        $this->assertBelongsToProject($risk, $project->id);
        $this->authorize('delete', $risk);

        $risk->delete();

        return response()->json(['message' => 'Riesgo eliminado.']);
    }
}
