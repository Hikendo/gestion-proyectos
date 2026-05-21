<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'severity'    => $this->severity,
            'resolved'    => $this->resolved,
            'task'        => TaskResource::make($this->whenLoaded('task')),
            'created_at'  => $this->created_at->toDateString(),
        ];
    }
}
