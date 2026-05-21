<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProjectPlan\StoreProjectPlanRequest;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectPlanController extends Controller
{
    /**
     * GET /api/projects/{project}/plan
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return response()->json($project->plans()->latest()->first());
    }

    /**
     * POST /api/projects/{project}/plan
     */
    public function store(StoreProjectPlanRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $plan = $project->plans()->updateOrCreate(
            ['project_id' => $project->id],
            $request->validated()
        );

        return response()->json($plan, 201);
    }
}
