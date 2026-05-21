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

        return ObjectiveResource::collection(
            $project->objectives()->orderBy('type')->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/objectives
     */
    public function store(StoreObjectiveRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $objective = $project->objectives()->create($request->validated());

        return ObjectiveResource::make($objective)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/objectives/{objective}
     */
    public function update(UpdateObjectiveRequest $request, Project $project, Objective $objective): JsonResponse
    {
        $this->assertBelongsToProject($objective, $project->id);
        $this->authorize('update', $objective);

        $objective->update($request->validated());

        return ObjectiveResource::make($objective)->response();
    }
}
