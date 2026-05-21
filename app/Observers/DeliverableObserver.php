<?php

namespace App\Observers;

use App\Events\DeliverableApproved;
use App\Models\Deliverable;
use Illuminate\Support\Facades\Auth;

class DeliverableObserver
{
    public function updated(Deliverable $deliverable): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        if ($deliverable->wasChanged('approved') && $deliverable->approved) {
            DeliverableApproved::dispatch($deliverable, $actor);
        }
    }
}
