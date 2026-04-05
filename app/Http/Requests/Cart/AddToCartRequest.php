<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'design_id' => 'nullable|exists:designs,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'product_print_area_id' => 'required|exists:product_print_areas,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'design_prompt' => 'nullable|string|max:2000',
            'mockup_url' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório',
            'product_id.exists' => 'Produto não encontrado',
            'product_color_id.required' => 'A cor é obrigatória',
            'product_print_area_id.required' => 'A área de impressão é obrigatória',
            'quantity.required' => 'A quantidade é obrigatória',
            'quantity.min' => 'A quantidade mínima é 1',
            'unit_price.required' => 'O preço unitário é obrigatório',
        ];
    }
}
