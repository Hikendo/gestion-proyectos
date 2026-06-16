<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\TaskTimeLog;

class TaskTimeLogObserver
{
    /**
     * Al crear un registro de horas, suma las horas a worked_hours de la tarea.
     */
    public function created(TaskTimeLog $timeLog): void
    {
        $this->syncWorkedHours($timeLog->task);
    }

    /**
     * Al actualizar, ajusta las horas trabajadas.
     */
    public function updated(TaskTimeLog $timeLog): void
    {
        $this->syncWorkedHours($timeLog->task);
    }

    /**
     * Al eliminar, resta las horas de la tarea.
     */
    public function deleted(TaskTimeLog $timeLog): void
    {
        $this->syncWorkedHours($timeLog->task);
    }

    /**
     * Sincroniza worked_hours de la tarea con la suma de hours de sus time logs.
     * Usa DB::raw para evitar race conditions y luego dispara el observer de Task
     * para que recalcule el progreso.
     */
    protected function syncWorkedHours(Task $task): void
    {
        $totalMinutes = $task->timeLogs()->sum('minutes');
        $totalHours   = (int) round($totalMinutes / 60);

        if ((int) $task->worked_hours !== $totalHours) {
            $task->worked_hours = $totalHours;
            $task->save();
        }
    }
}
