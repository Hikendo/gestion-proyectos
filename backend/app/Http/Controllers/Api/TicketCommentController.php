<?php

namespace App\Http\Controllers\Api;

use App\Events\TicketCommentCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\TicketComment\StoreTicketCommentRequest;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use App\Traits\BelongsToProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    use BelongsToProject;

    /**
     * GET /api/projects/{project}/tickets/{ticket}/comments
     */
    public function index(Request $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorizeCommentView($request->user(), $ticket);

        try {
            $items = $ticket->comments()->with('user:id,name,email')->latest()->get();

            return response()->json([
                'status'  => true,
                'items'   => $items,
                'message' => 'Comentarios encontrados.',
            ]);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * POST /api/projects/{project}/tickets/{ticket}/comments
     */
    public function store(StoreTicketCommentRequest $request, Project $project, Ticket $ticket): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);

        try {
            $item = $ticket->comments()->create([
                'user_id' => $request->user()->id,
                'comment' => $request->validated('comment'),
            ]);

            event(new TicketCommentCreated($item, $request->user()));

            return response()->json([
                'status'  => true,
                'items'   => $item->load('user:id,name,email'),
                'message' => 'Comentario creado.',
            ], 201);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/projects/{project}/tickets/{ticket}/comments/{comment}
     */
    public function destroy(Request $request, Project $project, Ticket $ticket, int $commentId): JsonResponse
    {
        $this->assertBelongsToProject($ticket, $project->id);
        $this->authorizeCommentView($request->user(), $ticket);

        $comment = $ticket->comments()->findOrFail($commentId);

        abort_if(
            $comment->user_id !== $request->user()->id
                && ! $request->user()->hasRole('super-admin'),
            403
        );

        try {
            $comment->delete();

            return response()->json(['status' => true, 'items' => null, 'message' => 'Comentario eliminado.']);
        } catch (\Throwable $th) {
            return response()->json(['status' => false, 'items' => null, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Un usuario puede ver/eliminar comentarios si puede ver el ticket.
     * Los clientes quedan restringidos a sus propios tickets.
     */
    private function authorizeCommentView(User $user, Ticket $ticket): void
    {
        abort_unless($user->canForProject($ticket->project, 'ticket.view'), 403);

        if ($user->hasProjectRole($ticket->project, 'client')) {
            abort_unless($ticket->created_by === $user->id, 403);
        }
    }
}
