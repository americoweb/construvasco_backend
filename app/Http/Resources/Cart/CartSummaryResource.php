<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_id' => $this['cart_id'],
            'cart_uuid' => $this['cart_uuid'],
            'item_count' => $this['item_count'],
            'total_items' => $this['total_items'],
            'subtotal' => (float) $this['subtotal'],
            'formatted_subtotal' => $this['formatted_subtotal'],
            'currency' => $this['currency'],
            'expires_at' => $this['expires_at'],
            'is_empty' => $this['is_empty'],
        ];
    }
}
