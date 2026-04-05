<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Product\PrintAreaPosition;
use Illuminate\Validation\Rules\Enum;

class CreateProductPrintAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'position' => ['required', new Enum(PrintAreaPosition::class)],
            'description' => 'nullable|string|max:500',
            'max_width_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'additional_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da área de impressão é obrigatório',
            'position.required' => 'A posição é obrigatória',
        ];
    }
}
