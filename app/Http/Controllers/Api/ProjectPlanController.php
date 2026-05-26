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

        try {
            $item = $project->plans()->latest()->first();

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Plan encontrado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/plan
     */
    public function store(StoreProjectPlanRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        try {
            $item = $project->plans()->updateOrCreate(
                ['project_id' => $project->id],
                $request->validated()
            );

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Plan guardado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
