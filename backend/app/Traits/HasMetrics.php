<?php

namespace App\Traits;

use App\Jobs\RecalculateProjectMetricsJob;
use App\Jobs\RecalculateUserMetricsJob;

trait HasMetrics
{
    /**
     * Dispara el recálculo de métricas del proyecto en background.
     * Usar en Services o Listeners que modifiquen tareas/tickets/blockers.
     */
    public function refreshProjectMetrics(int $projectId): void
    {
        RecalculateProjectMetricsJob::dispatch($projectId);
    }

    /**
     * Dispara el recálculo de métricas del usuario en background.
     */
    public function refreshUserMetrics(int $userId): void
    {
        RecalculateUserMetricsJob::dispatch($userId);
    }
}
