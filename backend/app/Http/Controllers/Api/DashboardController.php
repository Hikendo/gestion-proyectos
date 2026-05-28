<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Enums\ProjectMemberRole;
use App\Models\Blocker;
use App\Models\Objective;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectPhase;
use App\Models\Risk;
use App\Models\Task;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/dashboard
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // ── Proyectos accesibles ───────────────────────────────────────────
            $projectIds = Project::query()
                ->where('owner_id', $user->id)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->pluck('id');

            // ── Roles del usuario en sus proyectos ────────────────────────────
            $isSuperAdmin = $user->isSuperAdmin();
            $isOwner      = Project::where('owner_id', $user->id)
                ->whereIn('id', $projectIds)
                ->exists();

            $memberRoles = ProjectMember::where('user_id', $user->id)
                ->whereIn('project_id', $projectIds)
                ->pluck('role')
                ->unique()
                ->toArray();

            // ── Permisos efectivos: unión de todos los roles de proyecto ───────
            // Se combina con los permisos Spatie globales del usuario.
            $effectivePermissions = collect($memberRoles)
                ->flatMap(fn($role) => ProjectMemberRole::permissionsFor($role))
                ->unique();

            $can = function (string $permission) use ($isSuperAdmin, $effectivePermissions, $user): bool {
                return $isSuperAdmin
                    || $effectivePermissions->contains($permission)
                    || $user->can($permission);
            };

            // Capacidades granulares derivadas de los permisos
            $canViewTasks      = $can('task.view');
            $canViewBlockers   = $can('blocker.view');
            $canViewRisks      = $can('risk.view');
            $canViewPhases     = $can('phase.view');
            $canViewObjectives = $can('objective.view');
            $canViewTickets    = $can('ticket.view');

            // Es manager si es super-admin, owner o tiene rol manager en algún proyecto
            $isManager = $isSuperAdmin
                || $isOwner
                || in_array('manager', $memberRoles);

            // Es cliente puro si NO puede ver tareas (client no tiene task.view)
            $isClient = !$canViewTasks && $canViewTickets;

            // ── Proyectos (todos los roles) ────────────────────────────────────
            $projects = Project::whereIn('id', $projectIds)
                ->select('id', 'name', 'code', 'status', 'progress', 'end_date')
                ->withCount(['tasks', 'tickets' => fn($q) => $q->where('status', 'open')])
                ->latest()
                ->take(10)
                ->get();

            // ── Tareas propias (roles con task.view) ───────────────────────────
            $myTasks = collect();
            if ($canViewTasks) {
                $myTasks = Task::whereIn('project_id', $projectIds)
                    ->where('assigned_to', $user->id)
                    ->whereNotIn('status', ['done'])
                    ->select('id', 'title', 'status', 'priority', 'due_date', 'project_id')
                    ->with('project:id,name,code')
                    ->orderBy('due_date')
                    ->take(10)
                    ->get();
            }

            // ── Tickets abiertos (roles con ticket.view, excepto clientes) ─────
            $openTickets = collect();
            if ($canViewTickets && !$isClient) {
                $openTickets = Ticket::whereIn('project_id', $projectIds)
                    ->where('status', 'open')
                    ->select('id', 'subject', 'priority', 'created_at', 'project_id')
                    ->with('project:id,name,code')
                    ->latest()
                    ->take(5)
                    ->get();
            }

            // ── Bloqueadores activos (roles con blocker.view) ──────────────────
            $activeBlockers = collect();
            if ($canViewBlockers) {
                $activeBlockers = Blocker::whereIn('project_id', $projectIds)
                    ->where('resolved', false)
                    ->select('id', 'title', 'severity', 'project_id', 'task_id')
                    ->with(['project:id,name,code', 'task:id,title'])
                    ->latest()
                    ->take(5)
                    ->get();
            }

            // ── Riesgos activos (roles con risk.view) ──────────────────────────
            $activeRisks = collect();
            if ($canViewRisks) {
                $activeRisks = Risk::whereIn('project_id', $projectIds)
                    ->where('status', 'active')
                    ->select('id', 'title', 'impact', 'probability', 'project_id')
                    ->with('project:id,name,code')
                    ->latest()
                    ->take(5)
                    ->get();
            }

            // ── Fases en curso (roles con phase.view) ──────────────────────────
            $activePhases = collect();
            if ($canViewPhases) {
                $activePhases = ProjectPhase::whereIn('project_id', $projectIds)
                    ->where('progress', '<', 100)
                    ->select('id', 'name', 'progress', 'end_date', 'project_id')
                    ->with('project:id,name,code')
                    ->orderBy('end_date')
                    ->take(5)
                    ->get();
            }

            // ── Objetivos pendientes (roles con objective.view: incluye client) ─
            $activeObjectives = collect();
            if ($canViewObjectives) {
                $activeObjectives = Objective::whereIn('project_id', $projectIds)
                    ->where('completed', false)
                    ->select('id', 'title', 'type', 'project_id', 'completed')
                    ->with('project:id,name,code')
                    ->latest()
                    ->take(5)
                    ->get();
            }

            // ── Mis tickets (solo clientes) ────────────────────────────────────
            $myTickets = collect();
            $clientOpenCount = 0;

            if ($isClient) {
                $myTickets = Ticket::whereIn('project_id', $projectIds)
                    ->where('created_by', $user->id)
                    ->select('id', 'subject', 'status', 'priority', 'created_at', 'project_id', 'assigned_to')
                    ->with([
                        'project:id,name,code',
                        'assignee:id,name,email',
                    ])
                    ->latest()
                    ->take(15)
                    ->get();

                $clientOpenCount = Ticket::whereIn('project_id', $projectIds)
                    ->where('created_by', $user->id)
                    ->whereNotIn('status', ['closed', 'resolved'])
                    ->count();
            }

            // ── Vista Manager ──────────────────────────────────────────────────
            $managerTickets = [];
            $managerTasks   = [];

            if ($isManager) {
                $managerTickets = Ticket::whereIn('project_id', $projectIds)
                    ->whereNotIn('status', ['closed'])
                    ->select('id', 'subject', 'status', 'priority', 'created_by', 'assigned_to', 'project_id', 'created_at')
                    ->with([
                        'project:id,name,code',
                        'creator:id,name,email',
                        'assignee:id,name,email',
                    ])
                    ->latest()
                    ->take(10)
                    ->get();

                $managerTasks = Task::whereIn('project_id', $projectIds)
                    ->whereNotIn('status', ['done'])
                    ->select('id', 'title', 'status', 'priority', 'assigned_to', 'project_id', 'due_date')
                    ->with([
                        'project:id,name,code',
                        'assignee:id,name,email',
                    ])
                    ->orderBy('due_date')
                    ->take(10)
                    ->get();
            }

            return response()->json([
                'status'  => true,
                'items'   => [
                    'summary' => [
                        'total_projects'        => $projectIds->count(),
                        'my_pending_tasks'       => $myTasks->count(),
                        'open_tickets'           => $isClient ? $clientOpenCount : $openTickets->count(),
                        'active_blockers_count'  => $activeBlockers->count(),
                        'active_risks_count'     => $activeRisks->count(),
                    ],
                    // Flags de capacidad para el frontend
                    'is_manager'         => $isManager,
                    'is_client'          => $isClient,
                    'can_view_tasks'     => $canViewTasks,
                    'can_view_blockers'  => $canViewBlockers,
                    'can_view_risks'     => $canViewRisks,
                    'can_view_phases'    => $canViewPhases,
                    'can_view_objectives'=> $canViewObjectives,
                    // Datos
                    'projects'          => $projects,
                    'my_tasks'          => $myTasks,
                    'open_tickets'      => $openTickets,
                    'active_blockers'   => $activeBlockers,
                    'active_risks'      => $activeRisks,
                    'active_phases'     => $activePhases,
                    'active_objectives' => $activeObjectives,
                    'my_tickets'        => $myTickets,
                    'manager_tickets'   => $managerTickets,
                    'manager_tasks'     => $managerTasks,
                ],
                'message' => 'Dashboard cargado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }
}
