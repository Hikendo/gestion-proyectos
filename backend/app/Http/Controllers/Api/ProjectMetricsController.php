<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Enums\BlockerSeverity;
use App\Enums\RiskImpact;
use App\Enums\RiskStatus;
use App\Enums\TaskStatus;
use App\Enums\TicketStatus;
use App\Models\Project;
use Illuminate\Http\JsonResponse;

class ProjectMetricsController extends Controller
{
    /**
     * GET /api/projects/{project}/metrics
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $project->load([
                'owner:id,name,email',
                'members.user:id,name,email',
                'tasks.assignee:id,name',
                'tickets',
                'risks',
                'blockers.createdBy:id,name',
                'objectives',
                'milestones',
                'deliverables',
            ]);

            // ── Tasks ─────────────────────────────────────────────────────────
            $tasks = $project->tasks;

            $tasksByStatus = collect(TaskStatus::cases())->map(fn ($s) => [
                'status' => $s->value,
                'label'  => $s->label(),
                'count'  => $tasks->filter(fn ($t) => $t->status === $s)->count(),
            ])->values();

            $tasksByMember = $project->members
                ->map(function ($member) use ($tasks) {
                    $mt = $tasks->where('assigned_to', $member->user_id);
                    return [
                        'user_id'   => $member->user_id,
                        'name'      => $member->user?->name ?? '—',
                        'role'      => $member->role,
                        'total'     => $mt->count(),
                        'completed' => $mt->filter(fn ($t) => $t->status === TaskStatus::Done)->count(),
                        'blocked'   => $mt->filter(fn ($t) => $t->status === TaskStatus::Blocked)->count(),
                    ];
                })
                ->filter(fn ($m) => $m['total'] > 0)
                ->values();

            // ── Tickets ───────────────────────────────────────────────────────
            $tickets = $project->tickets;

            $ticketsByStatus = collect(TicketStatus::cases())->map(fn ($s) => [
                'status' => $s->value,
                'label'  => $s->label(),
                'count'  => $tickets->filter(fn ($t) => $t->status === $s)->count(),
            ])->values();

            // ── Risks ─────────────────────────────────────────────────────────
            $risks         = $project->risks;
            $risksActive   = $risks->filter(fn ($r) => ! $r->status || $r->status === RiskStatus::Active)->count();
            $risksResolved = $risks->filter(fn ($r) => $r->status && $r->status !== RiskStatus::Active)->count();

            $risksByImpact = collect(RiskImpact::cases())
                ->map(fn ($i) => [
                    'impact' => $i->value,
                    'label'  => $i->label(),
                    'count'  => $risks->filter(fn ($r) => $r->impact === $i)->count(),
                ])
                ->filter(fn ($i) => $i['count'] > 0)
                ->values();

            // ── Blockers ──────────────────────────────────────────────────────
            $blockers         = $project->blockers;
            $blockersActive   = $blockers->where('resolved', false)->count();
            $blockersResolved = $blockers->where('resolved', true)->count();

            $blockersBySeverity = collect(BlockerSeverity::cases())
                ->map(fn ($s) => [
                    'severity' => $s->value,
                    'label'    => $s->label(),
                    'count'    => $blockers->filter(fn ($b) => $b->severity === $s)->count(),
                ])
                ->filter(fn ($s) => $s['count'] > 0)
                ->values();

            $blockersByCreator = $blockers
                ->whereNotNull('created_by')
                ->groupBy('created_by')
                ->map(fn ($group) => [
                    'user_id' => $group->first()->created_by,
                    'name'    => $group->first()->creator?->name ?? 'Desconocido',
                    'count'   => $group->count(),
                ])
                ->values();

            // ── Objectives ────────────────────────────────────────────────────
            $objectives          = $project->objectives;
            $objectivesCompleted = $objectives->where('completed', true)->count();

            $objectivesByType = $objectives
                ->groupBy(fn ($o) => $o->type instanceof \BackedEnum ? $o->type->value : $o->type)
                ->map(fn ($g, $k) => [
                    'type'      => $k,
                    'total'     => $g->count(),
                    'completed' => $g->where('completed', true)->count(),
                ])
                ->values();

            // ── Milestones ────────────────────────────────────────────────────
            $milestones          = $project->milestones;
            $milestonesCompleted = $milestones->where('completed', true)->count();

            // ── Deliverables ──────────────────────────────────────────────────
            $deliverables = $project->deliverables;
            $delivApproved = $deliverables->where('approved', true)->count();

            // ── Response ──────────────────────────────────────────────────────
            return response()->json([
                'status'  => true,
                'message' => 'Métricas del proyecto.',
                'items'   => [
                    'project' => [
                        'id'         => $project->id,
                        'name'       => $project->name,
                        'status'     => $project->status,
                        'progress'   => $project->progress,
                        'start_date' => $project->start_date?->toDateString(),
                        'end_date'   => $project->end_date?->toDateString(),
                        'budget'     => $project->budget,
                        'owner'      => $project->owner,
                        'members'    => $project->members->map(fn ($m) => [
                            'user_id' => $m->user_id,
                            'name'    => $m->user?->name ?? '—',
                            'email'   => $m->user?->email,
                            'role'    => $m->role,
                        ]),
                    ],
                    'tasks' => [
                        'total'       => $tasks->count(),
                        'completed'   => $tasks->filter(fn ($t) => $t->status === TaskStatus::Done)->count(),
                        'in_progress' => $tasks->filter(fn ($t) => $t->status === TaskStatus::InProgress)->count(),
                        'pending'     => $tasks->filter(fn ($t) => $t->status === TaskStatus::Pending)->count(),
                        'blocked'     => $tasks->filter(fn ($t) => $t->status === TaskStatus::Blocked)->count(),
                        'by_status'   => $tasksByStatus,
                        'by_member'   => $tasksByMember,
                    ],
                    'tickets' => [
                        'total'       => $tickets->count(),
                        'open'        => $tickets->filter(fn ($t) => $t->status === TicketStatus::Open)->count(),
                        'in_progress' => $tickets->filter(fn ($t) => $t->status === TicketStatus::InProgress)->count(),
                        'resolved'    => $tickets->filter(fn ($t) => $t->status === TicketStatus::Resolved)->count(),
                        'closed'      => $tickets->filter(fn ($t) => $t->status === TicketStatus::Closed)->count(),
                        'by_status'   => $ticketsByStatus,
                    ],
                    'risks' => [
                        'total'      => $risks->count(),
                        'active'     => $risksActive,
                        'resolved'   => $risksResolved,
                        'by_impact'  => $risksByImpact,
                    ],
                    'blockers' => [
                        'total'       => $blockers->count(),
                        'active'      => $blockersActive,
                        'resolved'    => $blockersResolved,
                        'by_severity' => $blockersBySeverity,
                        'by_creator'  => $blockersByCreator,
                    ],
                    'objectives' => [
                        'total'     => $objectives->count(),
                        'completed' => $objectivesCompleted,
                        'pending'   => $objectives->count() - $objectivesCompleted,
                        'by_type'   => $objectivesByType,
                    ],
                    'milestones' => [
                        'total'     => $milestones->count(),
                        'completed' => $milestonesCompleted,
                        'pending'   => $milestones->count() - $milestonesCompleted,
                    ],
                    'deliverables' => [
                        'total'    => $deliverables->count(),
                        'approved' => $delivApproved,
                        'pending'  => $deliverables->count() - $delivApproved,
                    ],
                ],
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'status'  => false,
                'items'   => null,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
