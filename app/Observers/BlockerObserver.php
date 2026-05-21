<?php

namespace App\Observers;

use App\Events\BlockerCreated;
use App\Events\BlockerResolved;
use App\Models\Blocker;
use Illuminate\Support\Facades\Auth;

class BlockerObserver
{
    public function created(Blocker $blocker): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        BlockerCreated::dispatch($blocker, $actor);
    }

    public function updated(Blocker $blocker): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        if ($blocker->wasChanged('resolved') && $blocker->resolved) {
            BlockerResolved::dispatch($blocker, $actor);
        }
    }
}
