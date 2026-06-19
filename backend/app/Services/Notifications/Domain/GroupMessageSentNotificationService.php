<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Collection;

class GroupMessageSentNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'new_group_message';
    }

    /**
     * Notify all project members except the sender.
     */
    public function notify(ProjectMessage $message): void
    {
        $project = $message->project;

        // Get all members except the sender
        $members = $project->members()->with('user')
            ->get()
            ->pluck('user')
            ->filter(fn(User $user) => $user->id !== $message->user_id);

        // Also include owner if not the sender
        $owner = $project->owner;
        $recipients = collect([$owner])
            ->merge($members)
            ->filter(fn(User $user) => $user->id !== $message->user_id)
            ->unique('id');

        // No se necesita filtro de policy: todos los miembros tienen acceso al chat grupal
        $this->dispatchToMany(
            recipients: $recipients->values(),
            title: 'Nuevo mensaje en el chat del equipo',
            body: "{$message->user->name}: " . \Illuminate\Support\Str::limit($message->content, 100),
            data: [
                'project_id' => $project->id,
                'message_id' => $message->id,
                'sender_name' => $message->user->name,
            ],
            clickAction: null,
            icon: null,
            image: null,
        );
    }
}