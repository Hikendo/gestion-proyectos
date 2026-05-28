<?php

namespace App\Observers;

use App\Events\MilestoneCompleted;
use App\Models\Milestone;
use Illuminate\Support\Facades\Auth;

class MilestoneObserver
{
    public function updated(Milestone $milestone): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        if ($milestone->wasChanged('completed') && $milestone->completed) {
            MilestoneCompleted::dispatch($milestone, $actor);
        }
    }
}
