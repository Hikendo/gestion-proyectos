<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RiskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'description'     => $this->description,
            'impact'          => $this->impact,
            'probability'     => $this->probability,
            'mitigation_plan' => $this->mitigation_plan,
            'created_at'      => $this->created_at->toDateString(),
        ];
    }
}
