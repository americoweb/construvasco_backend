<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'payment_method' => $this->payment_method,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'total_items' => $this->total_items,
            'items_count' => $this->items_count ?? $this->items->count(),
            'shipping_name' => $this->shipping_name,
            'shipping_address' => $this->shipping_address,
            'shipping_city' => $this->shipping_city,
            'shipping_state' => $this->shipping_state,
            'shipping_whatsapp' => $this->shipping_whatsapp,
            'billing_email' => $this->billing_email,
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at->toIso8601String(),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product_name,
                        'color_name' => $item->color_name,
                        'print_area_name' => $item->print_area_name,
                        'quantity' => $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'mockup_url' => $item->mockup_url,
                    ];
                });
            }),
        ];
    }
}
