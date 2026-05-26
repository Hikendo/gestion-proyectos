<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreProjectMemberRequest;
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

        try {
            $items = $project->members()->with('user:id,name,email')->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Miembros encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/members
     */
    public function store(StoreProjectMemberRequest $request, Project $project): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        try {
            $item = $this->service->addMember(
                $project,
                $request->validated('user_id'),
                $request->validated('role')
            );

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Miembro agregado.',
            ], 201);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'items' => null, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/members/{user}
     */
    public function destroy(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        try {
            $this->service->removeMember($project, $userId);

            return response()->json(['status' => true, 'items' => null, 'message' => 'Miembro removido.']);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'items' => null, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
