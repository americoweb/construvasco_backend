<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'design_id' => $this->design_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'color_name' => $this->color_name,
            'color_hex_code' => $this->color_hex_code,
            'print_area_name' => $this->print_area_name,
            'design_prompt' => $this->design_prompt,
            'mockup_url' => $this->mockup_url,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'total_price' => (float) $this->total_price,
            'formatted_total' => $this->formatted_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
