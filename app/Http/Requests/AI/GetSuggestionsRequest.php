<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GetSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal' => 'required|string|min:10|max:500',
            'budget' => 'required|numeric|min:250|max:1000000',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'required_with:logo_base64|string|in:image/png,image/jpeg,image/webp',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'required_with:reference_image_base64|string|in:image/png,image/jpeg,image/webp',
            'max_suggestions' => 'sometimes|integer|min:1|max:10',
            'include_bundles' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'goal.required' => 'Descreva o seu objetivo ou necessidade',
            'goal.min' => 'Por favor, forneça mais detalhes sobre seu objetivo (mínimo 10 caracteres)',
            'budget.required' => 'Informe seu orçamento',
            'budget.numeric' => 'Orçamento deve ser um número',
            'budget.min' => 'Orçamento mínimo é 250 MT',
            'logo_mime_type.in' => 'Formato de logo inválido. Use PNG, JPEG ou WebP',
            'reference_image_mime_type.in' => 'Formato de imagem inválido. Use PNG, JPEG ou WebP',
        ];
    }
}
