<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class RefineDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_prompt' => 'required|string|max:1000',
            'feedback' => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'current_prompt.required' => 'Prompt atual é necessário',
            'feedback.required' => 'Forneça seu feedback para melhorar o design',
            'feedback.min' => 'Por favor, seja mais específico no seu feedback',
        ];
    }
}
