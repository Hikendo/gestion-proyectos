<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Project $project,
        public readonly User    $actor,
        /** Descripción legible del cambio, ej: "Estado cambiado a En progreso" */
        public readonly string  $changeDescription = ''
    ) {}
}
