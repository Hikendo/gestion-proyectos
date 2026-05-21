<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskTimeLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'minutes'     => $this->minutes,
            'hours'       => round($this->minutes / 60, 1),
            'description' => $this->description,
            'user'        => UserResource::make($this->whenLoaded('user')),
            'created_at'  => $this->created_at->toDateString(),
        ];
    }
}
