<?php

declare(strict_types=1);

namespace App\Services\Notifications\Domain;

use App\Models\Risk;
use App\Models\User;
use App\Services\Notifications\AbstractNotificationService;
use Illuminate\Support\Facades\Log;

final class RiskDetectedNotificationService extends AbstractNotificationService
{
    protected function notificationType(): string
    {
        return 'risk_detected';
    }

    public function notify(Risk $risk, User $actor): void
    {
        Log::channel('notifications')->info(
            "RiskDetectedNotificationService: riesgo ID {$risk->id} reportado por user ID {$actor->id}."
        );

        $risk->loadMissing('project');

        // Notificar a los managers del proyecto (rol manager + owner) y miembros con permiso risk.view
        $candidates = $this->resolver->resolveProjectMembers(
            project: $risk->project,
            excludeIds: [$actor->id],
        );

        $authorized = $this->policyFilter->filter($candidates, 'view', $risk);

        if ($authorized->isEmpty()) {
            return;
        }

        $this->dispatchToMany(
            recipients: $authorized,
            title: '⚠️ Riesgo detectado',
            body: "Nuevo riesgo \"{$risk->title}\" (impacto: {$risk->impact->value}, probabilidad: {$risk->probability->value}) en el proyecto \"{$risk->project->name}\".",
            data: [
                'type'       => $this->notificationType(),
                'risk_id'    => $risk->id,
                'risk_title' => $risk->title,
                'impact'     => $risk->impact->value,
                'probability' => $risk->probability->value,
                'project_id' => $risk->project_id,
                'url'        => config('app.url') . "/projects/{$risk->project_id}/risks/{$risk->id}",
            ],
            clickAction: config('app.url') . "/projects/{$risk->project_id}/risks/{$risk->id}",
        );
    }
}