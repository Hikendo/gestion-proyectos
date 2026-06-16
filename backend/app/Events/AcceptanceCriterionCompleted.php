<?php

namespace App\Events;

use App\Models\AcceptanceCriterion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcceptanceCriterionCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AcceptanceCriterion $criterion,
    ) {}
}
