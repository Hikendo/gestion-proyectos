<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $service) {}

    /**
     * GET /api/projects
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->with(['owner:id,name,email', 'metrics'])
            ->withCount(['tasks', 'tickets', 'risks', 'blockers'])
            ->latest()
            ->paginate(15);

        return ProjectResource::collection($projects)->response();
    }

    /**
     * POST /api/projects
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->service->create($request->validated(), $request->user());

        return ProjectResource::make($project->load('owner:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/projects/{project}
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project->load([
            'owner:id,name,email',
            'members.user:id,name,email',
            'phases',
            'objectives',
            'milestones',
            'deliverables',
            'risks',
            'blockers',
            'metrics',
        ])->loadCount(['tasks', 'tickets']);

        return ProjectResource::make($project)->response();
    }

    /**
     * PUT /api/projects/{project}
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return ProjectResource::make($project)->response();
    }

    /**
     * DELETE /api/projects/{project}
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Proyecto eliminado.']);
    }
}
