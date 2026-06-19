<?php

namespace App\Http\Controllers\Api;

use App\Events\DirectMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Project;
use App\Traits\HasProjectAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DirectChatController extends Controller
{
    use HasProjectAccess;

    /**
     * GET /api/v1/projects/{project}/conversations
     * List all conversations the user participates in within this project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->assertCanAccessProject($request->user(), $project);

        $user = $request->user();

        $conversations = Conversation::where('project_id', $project->id)
            ->where(function ($q) use ($user) {
                $q->where('user_one_id', $user->id)
                    ->orWhere('user_two_id', $user->id);
            })
            ->with(['userOne:id,name,email', 'userTwo:id,name,email'])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)->whereNull('read_at');
            }])
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($conversation) use ($user) {
                return [
                    'id' => $conversation->id,
                    'project_id' => $conversation->project_id,
                    'other_user' => [
                        'id' => $conversation->otherUser($user)->id,
                        'name' => $conversation->otherUser($user)->name,
                        'email' => $conversation->otherUser($user)->email,
                    ],
                    'unread_count' => $conversation->unread_count,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return response()->json(['data' => $conversations]);
    }

    /**
     * POST /api/v1/projects/{project}/conversations
     * Start or return an existing conversation with another member.
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $this->assertCanAccessProject($request->user(), $project);
        $this->assertProjectIsOpen($project);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $otherUserId = (int) $validated['user_id'];
        $currentUserId = $request->user()->id;

        // Cannot chat with yourself
        if ($otherUserId === $currentUserId) {
            return response()->json(['message' => 'No puedes iniciar un chat contigo mismo.'], 422);
        }

        // Verify the other user is a member of the project
        $isMember = $project->members()->where('user_id', $otherUserId)->exists()
            || $project->owner_id === $otherUserId;

        if (!$isMember) {
            return response()->json(['message' => 'El usuario no pertenece a este proyecto.'], 422);
        }

        // Find or create conversation (order user IDs to avoid duplicates)
        $userOneId = min($currentUserId, $otherUserId);
        $userTwoId = max($currentUserId, $otherUserId);

        $conversation = Conversation::firstOrCreate(
            [
                'project_id' => $project->id,
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]
        );

        return response()->json([
            'message' => 'Conversación lista.',
            'data' => [
                'id' => $conversation->id,
                'project_id' => $conversation->project_id,
                'other_user' => [
                    'id' => $otherUserId,
                ],
            ],
        ], 201);
    }

    /**
     * GET /api/v1/conversations/{conversation}/messages
     * Paginated list of messages in a conversation.
     */
    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        $messages = $conversation->messages()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }

    /**
     * POST /api/v1/conversations/{conversation}/messages
     * Send a new private message.
     */
    public function sendMessage(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        $project = $conversation->project;
        if ($project->status->isClosed()) {
            return response()->json(['message' => 'El proyecto está cerrado.'], 422);
        }

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $user->id,
            'content' => $validated['content'],
        ]);

        // Update conversation timestamp
        $conversation->touch();

        // Load user relation for broadcasting
        $message->load('user:id,name,email');

        // Broadcast the event
        broadcast(new DirectMessageSent($message))->toOthers();

        return response()->json([
            'message' => 'Mensaje enviado correctamente.',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'user_id' => $message->user_id,
                'user_name' => $message->user->name,
                'content' => $message->content,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * POST /api/v1/conversations/{conversation}/read
     * Mark all unread messages in the conversation as read.
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        if (!$conversation->hasParticipant($user)) {
            return response()->json(['message' => 'No tienes acceso a esta conversación.'], 403);
        }

        DirectMessage::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Mensajes marcados como leídos.']);
    }
}