<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class CreateRefinementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'refinement_prompt' => 'required|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'refinement_prompt.required' => 'A descrição do refinamento é obrigatória',
            'refinement_prompt.max' => 'A descrição não pode exceder 2000 caracteres',
        ];
    }
}
