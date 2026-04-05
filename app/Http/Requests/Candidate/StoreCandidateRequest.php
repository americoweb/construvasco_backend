<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Candidate\CandidateStatus;
use App\Enums\Candidate\ExperienceLevel;

class StoreCandidateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Handle authorization in middleware/policies
    }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:candidates,email',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|string|in:' . implode(',', array_column(CandidateStatus::cases(), 'value')),
            'source' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'experience_level' => 'nullable|string|in:' . implode(',', array_column(ExperienceLevel::cases(), 'value')),
            'location' => 'nullable|string|max:255',
            'availability' => 'nullable|date',
            'salary_expectation' => 'nullable|numeric|min:0',
            'preferred_work_type' => 'nullable|string|in:remote,onsite,hybrid,flexible',
            
            // Profile data
            'profile.summary' => 'nullable|string',
            'profile.linkedin_url' => 'nullable|url',
            'profile.github_url' => 'nullable|url',
            'profile.portfolio_url' => 'nullable|url',
            
            // Skills
            'skills' => 'nullable|array',
            'skills.*' => 'exists:skills,id',
            
            // Experience
            'experiences' => 'nullable|array',
            'experiences.*.company_name' => 'required|string|max:255',
            'experiences.*.job_title' => 'required|string|max:255',
            'experiences.*.start_date' => 'required|date',
            'experiences.*.end_date' => 'nullable|date|after:experiences.*.start_date',
            
            // Education
            'educations' => 'nullable|array',
            'educations.*.institution_name' => 'required|string|max:255',
            'educations.*.degree_type' => 'required|string|max:255',
            'educations.*.field_of_study' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'A candidate with this email already exists.',
            'experiences.*.end_date.after' => 'End date must be after start date.',
        ];
    }
}
