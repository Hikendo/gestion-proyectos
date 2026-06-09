<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RiskDetected;
use App\Jobs\LogActivityJob;
use App\Jobs\RecalculateProjectMetricsJob;
use App\Services\Notifications\Domain\RiskDetectedNotificationService;

class HandleRiskDetected
{
    public function __construct(
        private readonly RiskDetectedNotificationService $notificationService
    ) {}

    public function handle(RiskDetected $event): void
    {
        $this->notificationService->notify($event->risk, $event->actor);

        LogActivityJob::dispatch(
            userId: $event->actor->id,
            module: 'risk',
            action: 'detected',
            data: [
                'risk_id'    => $event->risk->id,
                'risk_title' => $event->risk->title,
                'impact'     => $event->risk->impact->value,
                'probability' => $event->risk->probability->value,
                'project_id' => $event->risk->project_id,
            ]
        );

        RecalculateProjectMetricsJob::dispatch($event->risk->project_id);
    }
}