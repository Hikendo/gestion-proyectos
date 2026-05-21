<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'subject'     => $this->subject,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'project'     => ProjectResource::make($this->whenLoaded('project')),
            'creator'     => UserResource::make($this->whenLoaded('creator')),
            'assignee'    => UserResource::make($this->whenLoaded('assignee')),
            'created_at'  => $this->created_at->toDateString(),
        ];
    }
}
