<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'status'          => $this->status,
            'priority'        => $this->priority,
            'progress'        => $this->progress,
            'due_date'        => $this->due_date?->toDateString(),
            'estimated_hours' => $this->estimated_hours,
            'worked_hours'    => $this->worked_hours,
            'project'         => ProjectResource::make($this->whenLoaded('project')),
            'phase'           => ProjectPhaseResource::make($this->whenLoaded('phase')),
            'assignee'        => UserResource::make($this->whenLoaded('assignee')),
            'creator'         => UserResource::make($this->whenLoaded('creator')),
            'comments'        => TaskCommentResource::collection($this->whenLoaded('comments')),
            'time_logs'       => TaskTimeLogResource::collection($this->whenLoaded('timeLogs')),
            'blockers'        => BlockerResource::collection($this->whenLoaded('blockers')),
            'created_at'      => $this->created_at->toDateString(),
        ];
    }
}
