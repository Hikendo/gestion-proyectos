<?php

namespace App\Observers;

use App\Models\Risk;

class RiskObserver
{
    /**
     * Handle the Risk "created" event.
     */
    public function created(Risk $risk): void
    {
        //
    }

    /**
     * Handle the Risk "updated" event.
     */
    public function updated(Risk $risk): void
    {
        //
    }

    /**
     * Handle the Risk "deleted" event.
     */
    public function deleted(Risk $risk): void
    {
        //
    }

    /**
     * Handle the Risk "restored" event.
     */
    public function restored(Risk $risk): void
    {
        //
    }

    /**
     * Handle the Risk "force deleted" event.
     */
    public function forceDeleted(Risk $risk): void
    {
        //
    }
}
