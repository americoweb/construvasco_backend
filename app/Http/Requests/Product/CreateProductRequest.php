<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\ProductStatus;
use Illuminate\Validation\Rules\Enum;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'min_quantity' => 'required|integer|min:1',
            'image_url' => 'nullable|url|max:500',
            'base_image_url' => 'nullable|url|max:500',
            'design_hint' => 'nullable|string|max:1000',
            'status' => ['nullable', new Enum(ProductStatus::class)],
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório',
            'price.required' => 'O preço é obrigatório',
            'price.numeric' => 'O preço deve ser um número válido',
            'min_quantity.required' => 'A quantidade mínima é obrigatória',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1',
        ];
    }
}
