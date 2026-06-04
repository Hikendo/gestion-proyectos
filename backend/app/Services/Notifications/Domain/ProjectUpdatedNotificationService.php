<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando un proyecto es actualizado.
 *
 * Destinatarios: todos los miembros activos del proyecto excepto el actor.
 * Policy check: project → view
 */
final class ProjectUpdatedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'project_updated';
    }

    public function notify(Project $project, User $actor, string $changeDescription = ''): void
    {
        Log::channel('notifications')->info(
            "ProjectUpdatedNotificationService: proyecto ID {$project->id} actualizado por user ID {$actor->id}."
        );

        $candidates = $this->resolver->resolveProjectMembers(
            project: $project,
            excludeIds: [$actor->id],
        );

        $authorized = $this->policyFilter->filter($candidates, 'view', $project);

        $body = $changeDescription
            ? "El proyecto \"{$project->name}\" fue actualizado: {$changeDescription}."
            : "El proyecto \"{$project->name}\" ha sido actualizado.";

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Proyecto actualizado',
            body: $body,
            data: [
                'type'        => $this->notificationType(),
                'project_id'  => $project->id,
                'project_name' => $project->name,
                'description' => $changeDescription,
                'url'         => config('app.url') . "/projects/{$project->id}",
            ],
            clickAction: config('app.url') . "/projects/{$project->id}",
        );
    }
}
