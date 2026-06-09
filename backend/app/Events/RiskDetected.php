<?php

namespace App\Events;

use App\Models\Risk;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiskDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Risk $risk,
        public readonly User $actor
    ) {}
}