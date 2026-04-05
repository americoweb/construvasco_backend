<?php

namespace App\Http\Resources\Candidate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'personal_info' => [
                'full_name' => $this->full_name,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'location' => $this->location,
            ],
            'professional_info' => [
                'status' => [
                    'value' => $this->status?->value,
                    'label' => $this->status?->label(),
                ],
                'experience_level' => [
                    'value' => $this->experience_level?->value,
                    'label' => $this->experience_level?->label(),
                ],
                'availability' => $this->availability?->format('Y-m-d'),
                'salary_expectation' => $this->salary_expectation,
                'preferred_work_type' => $this->preferred_work_type,
            ],
            'profile' => $this->whenLoaded('profile'),
            'skills' => $this->whenLoaded('skills'),
            'experiences' => $this->whenLoaded('experiences'),
            'educations' => $this->whenLoaded('educations'),
            'resumes' => $this->whenLoaded('resumes'),
            'metadata' => [
                'source' => $this->source,
                'notes' => $this->notes,
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],
        ];
    }
}
