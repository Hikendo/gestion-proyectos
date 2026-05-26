<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Objective\StoreObjectiveRequest;
use App\Http\Requests\Objective\UpdateObjectiveRequest;
use App\Http\Resources\ObjectiveResource;
use App\Models\Objective;
use App\Models\Project;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObjectiveController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/objectives
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $items = $project->objectives()->orderBy('type')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Objetivos encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/objectives
     */
    public function store(StoreObjectiveRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $item = $project->objectives()->create($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Objetivo creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/projects/{project}/objectives/{objective}
     */
    public function update(UpdateObjectiveRequest $request, Project $project, Objective $objective): JsonResponse
    {
        $this->assertBelongsToProject($objective, $project->id);
        $this->authorize('update', $objective);

        try {
            $objective->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $objective,
                'message' => 'Objetivo actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
