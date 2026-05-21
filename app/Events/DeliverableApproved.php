<?php

namespace App\Events;

use App\Models\Deliverable;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeliverableApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Deliverable $deliverable,
        public readonly User $actor
    ) {}
}
