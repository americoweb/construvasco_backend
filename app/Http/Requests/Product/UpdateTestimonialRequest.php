<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTestimonialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'sometimes|required|integer|exists:products,id',
            'text' => 'sometimes|required|string|min:10',
            'author' => 'sometimes|required|string|max:255',
            'photo_url' => 'nullable|url|max:500',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O ID do produto é obrigatório',
            'product_id.exists' => 'O produto selecionado não existe',
            'text.required' => 'O texto do depoimento é obrigatório',
            'text.min' => 'O texto do depoimento deve ter pelo menos 10 caracteres',
            'author.required' => 'O autor do depoimento é obrigatório',
            'author.max' => 'O nome do autor não pode exceder 255 caracteres',
            'photo_url.url' => 'A URL da foto deve ser válida',
            'photo_url.max' => 'A URL da foto não pode exceder 500 caracteres',
        ];
    }
}

