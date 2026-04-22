<?php

namespace App\Http\Requests\Design;

use Illuminate\Foundation\Http\FormRequest;

class CreateDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'product_print_area_id' => 'required|exists:product_print_areas,id',
            'prompt' => 'nullable|string|max:2000',
            'mockup_url' => 'nullable|string|max:1000',
            'mockup_base64' => 'nullable|string',
            'session_id' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|exists:users,id',
            'is_from_suggestion' => 'nullable|boolean',
            'suggestion_id' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório',
            'product_id.exists' => 'Produto não encontrado',
            'product_color_id.required' => 'A cor é obrigatória',
            'product_color_id.exists' => 'Cor não encontrada',
            'product_print_area_id.required' => 'A área de impressão é obrigatória',
            'product_print_area_id.exists' => 'Área de impressão não encontrada',
            'prompt.required_without' => 'A descrição do design é obrigatória quando não há logo',
            'prompt.max' => 'A descrição não pode exceder 2000 caracteres',
        ];
    }
}
