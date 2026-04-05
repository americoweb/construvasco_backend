<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'session_id' => 'nullable|string|max:255',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_phone' => 'nullable|string|max:20',
            'shipping_whatsapp' => 'required|string|max:20',
            'billing_name' => 'nullable|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            
            // Order items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.design_id' => 'nullable|exists:designs,id',
            'items.*.product_color_id' => 'required|exists:product_colors,id',
            'items.*.product_print_area_id' => 'required|exists:product_print_areas,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.color_name' => 'required|string|max:100',
            'items.*.color_hex_code' => 'required|string|max:7',
            'items.*.print_area_name' => 'required|string|max:100',
            'items.*.design_prompt' => 'nullable|string|max:2000',
            'items.*.mockup_url' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_name.required' => 'O nome de envio é obrigatório',
            'shipping_address.required' => 'O endereço de envio é obrigatório',
            'shipping_whatsapp.required' => 'O WhatsApp é obrigatório',
            'items.required' => 'O pedido deve conter pelo menos um item',
            'items.min' => 'O pedido deve conter pelo menos um item',
            'items.*.product_id.required' => 'O produto é obrigatório',
            'items.*.quantity.required' => 'A quantidade é obrigatória',
            'items.*.quantity.min' => 'A quantidade mínima é 1',
            'items.*.unit_price.required' => 'O preço unitário é obrigatório',
        ];
    }
}
