<?php

namespace App\Events;

use App\Models\Blocker;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BlockerResolved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Blocker $blocker,
        public readonly User $actor
    ) {}
}
