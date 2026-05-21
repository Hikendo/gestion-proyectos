<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'code'         => $this->code,
            'description'  => $this->description,
            'status'       => $this->status,
            'progress'     => $this->progress,
            'start_date'   => $this->start_date?->toDateString(),
            'end_date'     => $this->end_date?->toDateString(),
            'budget'       => $this->budget,
            'owner'        => UserResource::make($this->whenLoaded('owner')),
            'members'      => ProjectMemberResource::collection($this->whenLoaded('members')),
            'phases'       => ProjectPhaseResource::collection($this->whenLoaded('phases')),
            'objectives'   => ObjectiveResource::collection($this->whenLoaded('objectives')),
            'milestones'   => MilestoneResource::collection($this->whenLoaded('milestones')),
            'deliverables' => DeliverableResource::collection($this->whenLoaded('deliverables')),
            'risks'        => RiskResource::collection($this->whenLoaded('risks')),
            'blockers'     => BlockerResource::collection($this->whenLoaded('blockers')),
            'metrics'      => ProjectMetricResource::make($this->whenLoaded('metrics')),
            'tasks_count'  => $this->whenCounted('tasks'),
            'tickets_count' => $this->whenCounted('tickets'),
            'created_at'   => $this->created_at->toDateString(),
        ];
    }
}
