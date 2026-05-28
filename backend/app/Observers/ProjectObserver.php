<?php

namespace App\Observers;

use App\Events\ProjectCreated;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;

class ProjectObserver
{
    public function created(Project $project): void
    {
        $actor = Auth::user();

        if (! $actor) {
            return;
        }

        ProjectCreated::dispatch($project, $actor);
    }
}
