<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_tasks'      => $this->total_tasks,
            'completed_tasks'  => $this->completed_tasks,
            'open_tickets'     => $this->open_tickets,
            'total_blockers'   => $this->total_blockers,
            'completion_rate'  => $this->completion_rate,
        ];
    }
}
