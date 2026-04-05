<?php

namespace App\Http\Resources\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->uuid,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'shipping' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'country' => $this->shipping_country,
                'whatsapp' => $this->shipping_whatsapp,
            ],
            'billing_email' => $this->billing_email,
            'items_count' => $this->items->count(),
            'items' => $this->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'color_name' => $item->color_name,
                    'print_area_name' => $item->print_area_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'mockup_url' => $item->mockup_url,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
