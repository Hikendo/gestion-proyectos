<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Models\Project;
use App\Services\AttachmentService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function __construct(
        private ProjectService $service,
        private AttachmentService $attachmentService,
    ) {}

    /**
     * GET /api/projects
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Solo proyectos donde el usuario es owner o miembro
            $baseQuery = Project::where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            });

            $search = trim($request->get('query', ''));

            // Si no hay término de búsqueda, usamos Eloquent directamente porque
            // el driver "collection" de Scout devuelve vacío con search('').
            if ($search === '') {
                $items = $baseQuery->orderBy('id', 'DESC')->paginate(10);
            } else {
                $items = Project::search($search)
                    ->query(fn($q) => $q->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                            ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
                    }))
                    ->orderBy('id', 'DESC')
                    ->paginate(10);
            }

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
            $data = $request->validated();
            $files = $request->file('attachments', []);

            // Extraer attachments para que ProjectService no los procese
            unset($data['attachments']);

            $item = $this->service->create($data, $request->user());

            // Subir archivos adjuntos polimórficos con aislamiento por proyecto
            if (!empty($files)) {
                $this->attachmentService->uploadMany($item, $files, $request->user());
            }

            $item->load(['owner:id,name,email', 'attachments']);

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
                'attachments',
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

    /**
     * GET /api/projects/{project}/permissions
     *
     * Devuelve los permisos del usuario autenticado sobre el proyecto.
     * Usado por el composable useProjectPermission en el frontend.
     */
    public function permissions(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => false, 'items' => null, 'message' => 'No autenticado.'], 401);
        }

        // Verificar acceso básico de vista (sin lanzar excepción)
        $canView = Gate::check('view', $project);

        if (!$canView) {
            return response()->json(['status' => false, 'items' => null, 'message' => 'No tienes acceso a este proyecto.'], 403);
        }

        // Super-admin tiene todos los permisos vía gate-before, pero no es miembro
        if ($user->isSuperAdmin()) {
            $flatPermissions = \App\Enums\ProjectMemberRole::permissionsFor(\App\Enums\ProjectMemberRole::Manager);
            $membershipRole = 'manager';
        } else {
            $membershipRole = $user->projectMembershipRole($project);
            $flatPermissions = $membershipRole
                ? \App\Enums\ProjectMemberRole::permissionsFor($membershipRole)
                : [];

            // Owner sin membresía explícita: permisos de manager
            if ($project->owner_id === $user->id) {
                $flatPermissions = \App\Enums\ProjectMemberRole::permissionsFor(\App\Enums\ProjectMemberRole::Manager);
                $membershipRole = 'manager';
            }
        }

        return response()->json([
            'status' => true,
            'items' => [
                'can_view'            => true,
                'can_edit'            => Gate::check('update', $project),
                'can_delete'          => Gate::check('delete', $project),
                'can_assign_members'  => Gate::check('assignMembers', $project),
                'can_manage_attachments' => Gate::check('manageAttachments', $project),
                'is_owner'            => $project->owner_id === $user->id,
                'project_role'        => $membershipRole,
                'permissions'         => $flatPermissions,
            ],
            'message' => 'Permisos del proyecto.',
        ]);
    }

    /**
     * POST /api/v1/projects/{project}/attachments
     *
     * Sube múltiples archivos adjuntos a un proyecto existente.
     * Solo PM/owner pueden gestionar adjuntos del proyecto.
     */
    public function uploadAttachments(Request $request, Project $project): JsonResponse
    {
        $this->authorize('manageAttachments', $project);

        $request->validate([
            'attachments'   => ['required', 'array'],
            'attachments.*' => ['file', 'max:102400'],
        ]);

        try {
            $files = $request->file('attachments');
            $uploaded = $this->attachmentService->uploadMany($project, $files, $request->user());

            return response()->json([
                'status'  => true,
                'data'    => $uploaded,
                'message' => count($uploaded) . ' archivo(s) subido(s) correctamente.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
