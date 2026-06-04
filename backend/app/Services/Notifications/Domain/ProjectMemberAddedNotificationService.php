<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se agrega un nuevo miembro a un proyecto.
 *
 * Destinatarios:
 *  - El nuevo miembro (bienvenida)
 *  - El owner del proyecto (confirmación)
 * Policy check: project → view
 */
final class ProjectMemberAddedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'project_member_added';
    }

    public function notify(Project $project, User $newMember, User $actor): void
    {
        Log::channel('notifications')->info(
            "ProjectMemberAddedNotificationService: user ID {$newMember->id} " .
                "añadido al proyecto ID {$project->id}."
        );

        $project->loadMissing('owner.fcmTokens');
        $newMember->loadMissing('fcmTokens');

        $data = [
            'type'         => $this->notificationType(),
            'project_id'   => $project->id,
            'project_name' => $project->name,
            'new_member_id' => $newMember->id,
            'new_member'   => $newMember->name,
            'url'          => config('app.url') . "/projects/{$project->id}",
        ];

        // Notificar al nuevo miembro
        $memberCandidates = $this->policyFilter->filter(collect([$newMember]), 'view', $project);
        $this->dispatchToMany(
            recipients: $memberCandidates,
            title: 'Fuiste añadido a un proyecto',
            body: "Has sido añadido al proyecto \"{$project->name}\".",
            data: $data,
            clickAction: config('app.url') . "/projects/{$project->id}",
        );

        // Notificar al owner si es distinto del actor
        if ($project->owner && $project->owner->id !== $actor->id) {
            $ownerCandidates = $this->policyFilter->filter(collect([$project->owner]), 'view', $project);
            $this->dispatchToMany(
                recipients: $ownerCandidates,
                title: 'Nuevo miembro en tu proyecto',
                body: "\"{$newMember->name}\" se unió al proyecto \"{$project->name}\".",
                data: $data,
                clickAction: config('app.url') . "/projects/{$project->id}",
            );
        }
    }
}
