<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserMetricResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'assigned_tasks'    => $this->assigned_tasks,
            'completed_tasks'   => $this->completed_tasks,
            'worked_hours'      => round($this->worked_minutes / 60, 1),
            'performance_score' => $this->performance_score,
        ];
    }
}
