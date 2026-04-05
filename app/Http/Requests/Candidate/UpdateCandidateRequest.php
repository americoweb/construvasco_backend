<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Candidate\CandidateStatus;
use App\Enums\Candidate\ExperienceLevel;

class UpdateCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $candidateId = $this->route('candidate') ?? $this->route('id');
        
        return [
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:candidates,email,' . $candidateId,
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:' . implode(',', array_column(CandidateStatus::cases(), 'value')),
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'experience_level' => 'nullable|string|in:' . implode(',', array_column(ExperienceLevel::cases(), 'value')),
            'location' => 'nullable|string|max:255',
            'availability' => 'nullable|date',
            'salary_expectation' => 'nullable|numeric|min:0',
            'preferred_work_type' => 'nullable|string|in:remote,onsite,hybrid,flexible',
        ];
    }
}
