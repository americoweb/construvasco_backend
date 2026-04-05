<?php

namespace App\Http\Resources\Candidate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'experience_level' => [
                'value' => $this->experience_level?->value,
                'label' => $this->experience_level?->label(),
            ],
            'location' => $this->location,
            'skills_count' => $this->whenCounted('skills'),
            'has_resume' => $this->resumes()->exists(),
            'created_at' => $this->created_at->format('Y-m-d H:i'),
        ];
    }
}
