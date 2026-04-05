<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignRefinementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refinement_prompt' => $this->refinement_prompt,
            'previous_mockup_url' => $this->previous_mockup_url,
            'new_mockup' => $this->new_mockup,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
