<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Deliverable;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

final class DeliverableApprovedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'deliverable_approved';
    }

    public function notify(Deliverable $deliverable, User $actor): void
    {
        Log::channel('notifications')->info(
            "DeliverableApprovedNotificationService: entregable ID {$deliverable->id} aprobado por user ID {$actor->id}."
        );

        $deliverable->loadMissing('project.members.fcmTokens');

        // Notificar a todos los miembros del proyecto excepto al actor
        $candidates = $this->resolver->resolveProjectMembers(
            project: $deliverable->project,
            excludeIds: [$actor->id],
        );

        $authorized = $this->policyFilter->filter($candidates, 'view', $deliverable->project);

        $this->dispatchToMany(
            recipients: $authorized,
            title: 'Entregable aprobado',
            body: "El entregable \"{$deliverable->name}\" del proyecto \"{$deliverable->project->name}\" fue aprobado.",
            data: [
                'type'            => $this->notificationType(),
                'deliverable_id'  => $deliverable->id,
                'deliverable_name' => $deliverable->name,
                'project_id'      => $deliverable->project_id,
                'url'             => config('app.url') . "/projects/{$deliverable->project_id}/deliverables",
            ],
            clickAction: config('app.url') . "/projects/{$deliverable->project_id}/deliverables",
        );
    }
}