<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando se cambia el rol de un miembro en un proyecto.
 *
 * Destinatario: el miembro cuyo rol fue cambiado.
 * Policy check: project → view
 */
final class ProjectMemberRoleChangedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'project_member_role_changed';
    }

    public function notify(Project $project, User $member, string $newRole, User $actor): void
    {
        Log::channel('notifications')->info(
            "ProjectMemberRoleChangedNotificationService: rol de user ID {$member->id} " .
                "cambiado a \"{$newRole}\" en proyecto ID {$project->id}."
        );

        $member->loadMissing('fcmTokens');

        $data = [
            'type'         => $this->notificationType(),
            'project_id'   => $project->id,
            'project_name' => $project->name,
            'new_role'     => $newRole,
            'url'          => config('app.url') . "/projects/{$project->id}",
        ];

        $candidates = $this->policyFilter->filter(collect([$member]), 'view', $project);

        $this->dispatchToMany(
            recipients: $candidates,
            title: 'Tu rol en el proyecto ha cambiado',
            body: "Tu rol en \"{$project->name}\" ahora es \"{$newRole}\".",
            data: $data,
            clickAction: config('app.url') . "/projects/{$project->id}",
        );
    }
}