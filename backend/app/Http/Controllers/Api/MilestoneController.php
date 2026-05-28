<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\MilestoneException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Milestone\StoreMilestoneRequest;
use App\Http\Requests\Milestone\UpdateMilestoneRequest;
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

        try {
            $items = $project->milestones()->orderBy('target_date')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Milestones encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/milestones
     */
    public function store(StoreMilestoneRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $item = $project->milestones()->create($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Milestone creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
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

        try {
            $milestone->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $milestone,
                'message' => 'Milestone actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/milestones/{milestone}
     */
    public function destroy(Request $request, Project $project, Milestone $milestone): JsonResponse
    {
        $this->assertBelongsToProject($milestone, $project->id);
        $this->authorize('delete', $milestone);

        try {
            $milestone->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Milestone eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
    public function show(Request $request, Project $project, Milestone $milestone): JsonResponse
    {
        $this->assertBelongsToProject($milestone, $project->id);
        $this->authorize('view', $milestone);

        try {
            return response()->json([
                'status'  => true,
                'items'   => $milestone,
                'message' => 'Milestone encontrado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
