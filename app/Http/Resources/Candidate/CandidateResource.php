<?php

namespace App\Http\Resources\Candidate;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'full_name' => $this->full_name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => [
                'value' => $this->status?->value,
                'label' => $this->status?->label(),
            ],
            'source' => $this->source,
            'experience_level' => [
                'value' => $this->experience_level?->value,
                'label' => $this->experience_level?->label(),
            ],
            'location' => $this->location,
            'availability' => $this->availability?->format('Y-m-d'),
            'salary_expectation' => $this->salary_expectation,
            'preferred_work_type' => $this->preferred_work_type,
            'notes' => $this->notes,
            
            // Relationships
            'profile' => $this->whenLoaded('profile'),
            'skills' => $this->whenLoaded('skills', function () {
                return $this->skills->map(function ($skill) {
                    return [
                        'id' => $skill->id,
                        'name' => $skill->name,
                        'level' => $skill->pivot->level?->label(),
                        'years_experience' => $skill->pivot->years_experience,
                        'verified' => $skill->pivot->verified,
                    ];
                });
            }),
            'experiences' => $this->whenLoaded('experiences'),
            'educations' => $this->whenLoaded('educations'),
            'latest_resume' => $this->whenLoaded('resumes', function () {
                return $this->latest_resume;
            }),
            
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
