<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\ProductStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'min_quantity' => 'sometimes|integer|min:1',
            'image_url' => 'nullable|url|max:500',
            'base_image_url' => 'nullable|url|max:500',
            'design_hint' => 'nullable|string|max:1000',
            'status' => ['sometimes', new Enum(ProductStatus::class)],
            'is_featured' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'integer|exists:categories,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ];
    }
}
