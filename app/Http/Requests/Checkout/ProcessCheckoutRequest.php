<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_id' => 'required_without:cart_uuid|integer|exists:carts,id',
            'cart_uuid' => 'required_without:cart_id|string|uuid|exists:carts,uuid',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'sometimes|string|max:100',
            'shipping_state' => 'sometimes|string|max:100',
            'shipping_postal_code' => 'sometimes|string|max:20',
            'shipping_country' => 'sometimes|string|max:100',
            'shipping_phone' => 'sometimes|string|max:20',
            'shipping_whatsapp' => 'required|string|regex:/^\+258[0-9]{9}$/',
            'billing_name' => 'sometimes|string|max:255',
            'billing_email' => 'required|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'sometimes|numeric|min:0',
            'payment_method' => 'nullable|string|in:mpesa,emola,proof_upload',
            'payment_reference' => 'nullable|string|max:255',
            'payment_transaction_id' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_id.required_without' => 'Cart ID ou UUID é obrigatório',
            'cart_uuid.required_without' => 'Cart UUID ou ID é obrigatório',
            'cart_id.exists' => 'Carrinho não encontrado',
            'cart_uuid.exists' => 'Carrinho não encontrado',
            'shipping_name.required' => 'Nome completo é obrigatório',
            'shipping_address.required' => 'Endereço é obrigatório',
            'shipping_whatsapp.required' => 'WhatsApp é obrigatório',
            'shipping_whatsapp.regex' => 'WhatsApp deve estar no formato +258XXXXXXXXX',
            'billing_email.required' => 'Email é obrigatório',
            'billing_email.email' => 'Email inválido',
        ];
    }
}
