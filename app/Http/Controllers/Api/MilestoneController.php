<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MilestoneException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Milestone\StoreMilestoneRequest;
use App\Http\Requests\Milestone\UpdateMilestoneRequest;
use App\Http\Resources\MilestoneResource;
use App\Models\Milestone;
use App\Models\Project;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/milestones
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return MilestoneResource::collection(
            $project->milestones()->orderBy('target_date')->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/milestones
     */
    public function store(StoreMilestoneRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $milestone = $project->milestones()->create($request->validated());

        return MilestoneResource::make($milestone)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * PUT /api/projects/{project}/milestones/{milestone}
     */
    public function update(UpdateMilestoneRequest $request, Project $project, Milestone $milestone): JsonResponse
    {
        $this->assertBelongsToProject($milestone, $project->id);
        $this->authorize('update', $milestone);

        if ($milestone->completed && ! $request->has('completed')) {
            throw MilestoneException::alreadyCompleted();
        }

        $milestone->update($request->validated());

        return MilestoneResource::make($milestone)->response();
    }

    /**
     * DELETE /api/projects/{project}/milestones/{milestone}
     */
    public function destroy(Request $request, Project $project, Milestone $milestone): JsonResponse
    {
        $this->assertBelongsToProject($milestone, $project->id);
        $this->authorize('delete', $milestone);

        $milestone->delete();

        return response()->json(['message' => 'Milestone eliminado.']);
    }
}
