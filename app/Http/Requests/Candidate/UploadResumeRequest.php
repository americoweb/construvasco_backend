<?php

namespace App\Http\Requests\Candidate;

use Illuminate\Foundation\Http\FormRequest;

class UploadResumeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resume' => 'required|file|mimes:pdf,doc,docx|max:10240', // 10MB max
            'candidate_id' => 'required|exists:candidates,id',
        ];
    }

    public function messages(): array
    {
        return [
            'resume.mimes' => 'Resume must be a PDF, DOC, or DOCX file.',
            'resume.max' => 'Resume file size cannot exceed 10MB.',
        ];
    }
}
