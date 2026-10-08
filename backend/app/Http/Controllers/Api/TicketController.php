<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\TicketException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\StoreTicketRequest;
use App\Http\Requests\Ticket\UpdateTicketRequest;
use App\Models\Project;
use App\Models\Ticket;
use App\Services\AttachmentService;
use App\Services\FieldPermissionsService;
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
        private FieldPermissionsService $fieldPermissionsService,
    ) {}

    /**
     * GET /api/projects/{project}/tickets
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $user  = $request->user();
        $isClient = $user->hasProjectRole($project, 'client');

        try {
            $query = $request->string('query', '')->trim()->toString();

            // Si no hay término de búsqueda, usamos Eloquent directamente porque
            // el driver "collection" de Scout devuelve vacío con search('').
            if ($query === '') {
                $items = Ticket::where('project_id', $project->id)
                    ->with(['creator:id,name,email', 'assignee:id,name,email'])
                    ->when($isClient, fn($q) => $q->where('created_by', $user->id))
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
                            ->when($isClient, fn($q) => $q->where('created_by', $user->id))
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

            // Solo quien puede asignar (PM/owner/support) puede fijar el
            // responsable. El resto (p.ej. clientes) no puede establecerlo,
            // por lo que se ignora y el servicio asigna al PM por defecto.
            if (! $request->user()->canForProject($project, 'ticket.assign')) {
                unset($data['assigned_to']);
            }

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

            // Attach field-level permissions
            $ticket->field_permissions = $this->fieldPermissionsService->compute($request->user(), $ticket);

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

        $data = $request->validated();

        // Autorizar `assign` SOLO si el responsable realmente cambia. Así un
        // cliente (o cualquier editor sin permiso de asignación) puede
        // actualizar su ticket sin recibir un 403 por un assigned_to sin cambios.
        // La autorización se evalúa FUERA del try para que el 403 no se
        // convierta en un 500 al ser capturado por catch (\Throwable).
        $changesAssignment = array_key_exists('assigned_to', $data)
            && $data['assigned_to'] != $ticket->assigned_to;

        if ($changesAssignment) {
            $this->authorize('assign', $ticket);
        }

        try {
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
     * POST /api/v1/projects/{project}/tickets/{ticket}/attachments  (ruta heredada)
     *
     * Permite agregar adjuntos (evidencia) mientras el ticket no esté cerrado.
     * PM/owner pueden agregar en cualquier ticket; el creador del ticket
     * (p. ej. el cliente) también puede hacerlo aunque el ticket ya esté
     * En Progreso o Resuelto. La eliminación sigue restringida a manageAttachments.
     */
    public function uploadAttachments(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorize('addAttachments', $ticket);

        return $this->storeTicketAttachments($request, $ticket);
    }

    /**
     * POST /api/v1/tickets/{ticket}/attachments
     *
     * Variante sin proyecto (misma URL relativa que tasks/blockers) usada por
     * el frontend al subir adjuntos directamente a un ticket.
     */
    public function uploadTicketAttachments(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('addAttachments', $ticket);

        return $this->storeTicketAttachments($request, $ticket);
    }

    /**
     * Lógica compartida de almacenamiento de adjuntos de un ticket.
     */
    private function storeTicketAttachments(Request $request, Ticket $ticket): JsonResponse
    {
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
