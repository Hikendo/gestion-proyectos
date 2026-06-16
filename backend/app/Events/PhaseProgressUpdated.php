<?php

namespace App\Events;

use App\Models\ProjectPhase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PhaseProgressUpdated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ProjectPhase $phase,
    ) {}
}
