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
        try {
            $user = $request->user();

            $items = Project::search($request->string('search', ''))
                ->query(fn($q) => $q
                    ->where(fn($q) => $q
                        ->where('owner_id', $user->id)
                        ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
                    )
                    ->with(['owner:id,name,email', 'metrics'])
                    ->withCount(['tasks', 'tickets', 'risks', 'blockers'])
                    ->latest()
                )
                ->paginate(15);

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Proyectos encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        try {
            $item = $this->service->create($request->validated(), $request->user());
            $item->load('owner:id,name,email');

            return response()->json([
                'status'  => true,
                'items'   => $item,
                'message' => 'Proyecto creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
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

            return response()->json([
                'status'  => true,
                'items'   => $project,
                'message' => 'Proyecto encontrado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/projects/{project}
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        try {
            $project->update($request->validated());

            return response()->json([
                'status'  => true,
                'items'   => $project,
                'message' => 'Proyecto actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        try {
            $project->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Proyecto eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
