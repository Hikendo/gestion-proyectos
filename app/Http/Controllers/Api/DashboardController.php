<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
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
        $user = $request->user();

        $projectIds = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $projects = Project::whereIn('id', $projectIds)
            ->select('id', 'name', 'code', 'status', 'progress', 'end_date')
            ->withCount(['tasks', 'tickets' => fn($q) => $q->where('status', 'open')])
            ->latest()
            ->take(10)
            ->get();

        $myTasks = Task::whereIn('project_id', $projectIds)
            ->where('assigned_to', $user->id)
            ->whereNotIn('status', ['done'])
            ->select('id', 'title', 'status', 'priority', 'due_date', 'project_id')
            ->with('project:id,name,code')
            ->orderBy('due_date')
            ->take(10)
            ->get();

        $openTickets = Ticket::whereIn('project_id', $projectIds)
            ->where('status', 'open')
            ->select('id', 'subject', 'priority', 'created_at', 'project_id')
            ->with('project:id,name,code')
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'summary' => [
                'total_projects'   => $projectIds->count(),
                'my_pending_tasks' => $myTasks->count(),
                'open_tickets'     => $openTickets->count(),
            ],
            'projects'     => $projects,
            'my_tasks'     => $myTasks,
            'open_tickets' => $openTickets,
        ]);
    }
}
