<?php

namespace App\Http\Resources\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_uuid' => $this->resource['cart_uuid'],
            'items' => $this->resource['items'],
            'subtotal' => $this->resource['subtotal'],
            'formatted_subtotal' => $this->resource['formatted_subtotal'],
            'total_items' => $this->resource['total_items'],
            'shipping_options' => $this->resource['shipping_options'],
        ];
    }
}
