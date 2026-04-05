<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class GetCheckoutSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_uuid' => 'required|string|uuid|exists:carts,uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_uuid.required' => 'Cart UUID é obrigatório',
            'cart_uuid.uuid' => 'UUID inválido',
            'cart_uuid.exists' => 'Carrinho não encontrado',
        ];
    }
}
