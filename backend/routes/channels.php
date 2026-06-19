<?php

use App\Models\Conversation;
use App\Models\Project;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Project group chat channel.
 * Only project members can subscribe.
 */
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    $project = Project::find($projectId);

    if (!$project) {
        return false;
    }

    // Owner or member
    return $project->owner_id === $user->id
        || $project->members()->where('user_id', $user->id)->exists();
});

/**
 * Direct conversation channel.
 * Only the two participants can subscribe.
 */
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    return $conversation->hasParticipant($user);
});