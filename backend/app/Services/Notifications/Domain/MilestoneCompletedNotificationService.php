<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Milestone;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

/**
 * Notificación cuando un milestone es completado.
 *
 * Destinatarios: todos los miembros activos del proyecto.
 * Policy check: project → view
 */
final class MilestoneCompletedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'milestone_completed';
    }

    public function notify(Milestone $milestone, User $actor): void
    {
        Log::channel('notifications')->info(
            "MilestoneCompletedNotificationService: milestone ID {$milestone->id} " .
                "completado por user ID {$actor->id}."
        );

        $milestone->loadMissing('project');

        $candidates = $this->resolver->resolveProjectMembers(
            project: $milestone->project,
            excludeIds: [$actor->id],
        );

        // Verificamos que los destinatarios puedan ver el proyecto
        $authorized = $this->policyFilter->filter($candidates, 'view', $milestone->project);

        $this->dispatchToMany(
            recipients: $authorized,
            title: '🏆 Milestone alcanzado',
            body: "El milestone \"{$milestone->name}\" del proyecto \"{$milestone->project->name}\" fue completado.",
            data: [
                'type'          => $this->notificationType(),
                'milestone_id'  => $milestone->id,
                'milestone_name' => $milestone->name,
                'project_id'    => $milestone->project_id,
                'url'           => config('app.url') . "/projects/{$milestone->project_id}/milestones",
            ],
            clickAction: config('app.url') . "/projects/{$milestone->project_id}/milestones",
        );
    }
}
