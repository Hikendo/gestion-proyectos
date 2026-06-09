<?php

namespace App\Observers;

use App\Events\RiskDetected;
use App\Models\Risk;
use Illuminate\Support\Facades\Auth;

class RiskObserver
{
    /**
     * Dispara RiskDetected al crear un nuevo riesgo.
     */
    public function created(Risk $risk): void
    {
        $actor = Auth::user();

        if ($actor) {
            RiskDetected::dispatch($risk, $actor);
        }
    }
}