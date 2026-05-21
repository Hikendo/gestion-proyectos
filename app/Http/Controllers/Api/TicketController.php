<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Project;
use App\Models\Ticket;
use App\Services\ProjectService;
use App\Services\TicketService;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use BelongsToProject;

    public function __construct(
        private TicketService $service,
        private ProjectService $projectService
    ) {}

    /**
     * GET /api/projects/{project}/tickets
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $tickets = $project->tickets()
            ->with(['creator:id,name,email', 'assignee:id,name,email'])
            ->when($request->status,   fn($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
            ->latest()
            ->paginate(20);

        return TicketResource::collection($tickets)->response();
    }

    /**
     * POST /api/projects/{project}/tickets
     */
    public function store(StoreTicketRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $ticket = $this->service->create($request->validated(), $project, $request->user());

        return TicketResource::make($ticket->load('creator:id,name,email'))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /api/projects/{project}/tickets/{ticket}
     */
    public function show(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('view', $ticket);

        return TicketResource::make($ticket->load([
            'creator:id,name,email',
            'assignee:id,name,email',
        ]))->response();
    }

    /**
     * PUT /api/projects/{project}/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);

        if ($ticket->status->isClosed()) {
            throw TicketException::alreadyClosed(); // ← era isClosed()
        }

        $this->authorize('update', $ticket);

        $data = $request->validated();

        if (isset($data['assigned_to'])) {
            $this->authorize('assign', $ticket);
        }

        $ticket->update($data);

        return TicketResource::make($ticket->load('assignee:id,name,email'))->response();
    }

    /**
     * DELETE /api/projects/{project}/tickets/{ticket}
     */
    public function destroy(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json(['message' => 'Ticket eliminado.']);
    }
}
