<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ProjectException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreProjectMemberRequest;
use App\Http\Resources\ProjectMemberResource;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function __construct(private ProjectService $service) {}

    /**
     * GET /api/projects/{project}/members
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        return ProjectMemberResource::collection(
            $project->members()->with('user:id,name,email')->get()
        )->response();
    }

    /**
     * POST /api/projects/{project}/members
     */
    public function store(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        $member = $this->service->addMember(
            $project,
            $request->validated('user_id'),
            $request->validated('role')
        );

        return ProjectMemberResource::make($member->load('user:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * DELETE /api/projects/{project}/members/{user}
     */
    public function destroy(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        $this->service->removeMember($project, $userId);

        return response()->json(['message' => 'Miembro removido.']);
    }
}
