<?php

namespace App\Observers;

use App\Events\AcceptanceCriterionCompleted;
use App\Models\AcceptanceCriterion;

class AcceptanceCriterionObserver
{
    /**
     * Cuando un criterio se marca como completado, dispara el evento.
     */
    public function updated(AcceptanceCriterion $criterion): void
    {
        if ($criterion->isDirty('completed') && $criterion->completed) {
            AcceptanceCriterionCompleted::dispatch($criterion);
        }
    }
}
