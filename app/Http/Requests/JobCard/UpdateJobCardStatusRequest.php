<?php

namespace App\Http\Requests\JobCard;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\JobCard\JobCardStatus;

class UpdateJobCardStatusRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'status' => 'required|in:' . implode(',', array_column(JobCardStatus::cases(), 'value')),
            'notes'  => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in'       => 'Status inválido.',
        ];
    }
}
