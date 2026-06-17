<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\StoreProjectMemberRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\Domain\ProjectMemberAddedNotificationService;
use App\Services\Notifications\Domain\ProjectMemberRoleChangedNotificationService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function __construct(
        private ProjectService $service,
        private ProjectMemberAddedNotificationService $memberAddedNotification,
        private ProjectMemberRoleChangedNotificationService $memberRoleChangedNotification,
    ) {}

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

            // Notificar al nuevo miembro y al owner
            $newUser = User::find($request->validated('user_id'));
            if ($newUser) {
                $this->memberAddedNotification->notify($project, $newUser, $request->user());
            }

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
     * PUT /api/projects/{project}/members/{user}
     */
    public function update(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        $request->validate([
            'role' => ['required', 'string', 'in:manager,developer,qa,analyst,client'],
        ]);

        try {
            $newRole = $request->input('role');
            $item = $this->service->updateMember($project, $userId, $newRole);

            // Notificar al usuario cuyo rol fue cambiado
            $memberUser = User::find($userId);
            if ($memberUser) {
                $this->memberRoleChangedNotification->notify(
                    $project,
                    $memberUser,
                    $newRole,
                    $request->user()
                );
            }

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Rol de miembro actualizado.',
            ]);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'items' => null, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/members/{member}
     */
    public function show(Request $request, Project $project, \App\Models\ProjectMember $member): JsonResponse
    {
        $this->authorize('view', $project);
        abort_if($member->project_id !== $project->id, 404);

        return response()->json([
            'status'  => true,
            'items'   => $member->load('user:id,name,email'),
            'message' => 'Miembro encontrado.',
        ]);
    }

    /**
     * GET /api/projects/{project}/members/users
     *
     * Devuelve los miembros del proyecto como usuarios planos [{id, name, email}].
     * Usado por los formularios de tareas y tickets para el selector "Asignado a".
     */
    public function users(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $users = $project->members()
            ->with('user:id,name,email')
            ->get()
            ->map(fn($m) => $m->user)
            ->filter()
            ->values();

        return response()->json([
            'status'  => true,
            'items'   => $users,
            'message' => 'Usuarios miembros encontrados.',
        ]);
    }

    /**
     * PATCH /api/projects/{project}/members/{user}/suspend
     */
    public function suspend(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        try {
            $item = $this->service->suspendMember($project, $userId);

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Miembro suspendido.',
            ]);
        } catch (\App\Exceptions\DomainException $e) {
            return response()->json(['status' => false, 'items' => null, 'message' => $e->getMessage()], $e->getStatusCode());
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PATCH /api/projects/{project}/members/{user}/unsuspend
     */
    public function unsuspend(Request $request, Project $project, int $userId): JsonResponse
    {
        $this->authorize('assignMembers', $project);

        try {
            $item = $this->service->unsuspendMember($project, $userId);

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Miembro reactivado.',
            ]);
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
