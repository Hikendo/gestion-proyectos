<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Project;
use App\Models\Ticket;
use App\Services\AttachmentService;
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
        private ProjectService $projectService,
        private AttachmentService $attachmentService,
    ) {}

    /**
     * GET /api/projects/{project}/tickets
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $query = $request->string('query', '')->trim()->toString();

            // Si no hay término de búsqueda, usamos Eloquent directamente porque
            // el driver "collection" de Scout devuelve vacío con search('').
            if ($query === '') {
                $items = Ticket::where('project_id', $project->id)
                    ->with(['creator:id,name,email', 'assignee:id,name,email'])
                    ->when($request->status,   fn($q, $s) => $q->where('status', $s))
                    ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
                    ->latest()
                    ->paginate(20);
            } else {
                $items = Ticket::search($query)
                    ->query(
                        fn($q) => $q
                            ->where('project_id', $project->id)
                            ->with(['creator:id,name,email', 'assignee:id,name,email'])
                            ->when($request->status,   fn($q, $s) => $q->where('status', $s))
                            ->when($request->priority, fn($q, $p) => $q->where('priority', $p))
                            ->latest()
                    )
                    ->paginate(20);
            }

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Tickets encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/tickets
     */
    public function store(StoreTicketRequest $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        try {
            $data = $request->validated();
            $files = $request->file('attachments', []);

            unset($data['attachments']);

            $item = $this->service->create($data, $project, $request->user());

            if (!empty($files)) {
                $this->attachmentService->uploadMany($item, $files, $request->user());
            }

            return response()->json([
                'status'  => true,
                'items'   => $item->load(['creator:id,name,email', 'attachments']),
                'message' => 'Ticket creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * GET /api/projects/{project}/tickets/{ticket}
     */
    public function show(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('view', $ticket);

        try {
            $ticket->load([
                'creator:id,name,email',
                'assignee:id,name,email',
                'attachments',
            ]);

            return response()->json([
                'status'  => true,
                'items'   => $ticket,
                'message' => 'Ticket encontrado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * PUT /api/projects/{project}/tickets/{ticket}
     */
    public function update(UpdateTicketRequest $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);

        if ($ticket->status->isClosed()) {
            throw TicketException::alreadyClosed();
        }

        $this->authorize('update', $ticket);

        try {
            $data = $request->validated();

            if (isset($data['assigned_to'])) {
                $this->authorize('assign', $ticket);
            }

            $ticket->update($data);

            return response()->json([
                'status'  => true,
                'items'   => $ticket->load('assignee:id,name,email'),
                'message' => 'Ticket actualizado.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/tickets/{ticket}
     */
    public function destroy(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('delete', $ticket);

        try {
            $ticket->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Ticket eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/v1/projects/{project}/tickets/{ticket}/attachments
     *
     * Solo PM/owner pueden gestionar adjuntos de tickets.
     */
    public function uploadAttachments(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('manageAttachments', $ticket);

        $request->validate([
            'attachments'   => ['required', 'array'],
            'attachments.*' => ['file', 'max:102400'],
        ]);

        try {
            $files = $request->file('attachments');
            $uploaded = $this->attachmentService->uploadMany($ticket, $files, $request->user());

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
