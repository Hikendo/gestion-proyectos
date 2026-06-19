<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Traits\BelongsToProject;
use App\Traits\HasProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChatController extends Controller
{
    use BelongsToProject, HasProjectAccess;

    /**
     * GET /api/v1/projects/{project}/chat/messages
     * Paginated list of group messages.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->assertCanAccessProject($request->user(), $project);

        $messages = $project->groupMessages()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * POST /api/v1/projects/{project}/chat/messages
     * Send a new group message.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->assertCanAccessProject($request->user(), $project);
        $this->assertProjectIsOpen($project);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $message = $project->groupMessages()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        // Load user relation for broadcasting
        $message->load('user:id,name,email');

        // Broadcast the event
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
            'data' => [
                'id' => $message->id,
                'project_id' => $message->project_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }
}