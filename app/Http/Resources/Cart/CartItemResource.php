<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'design_id' => $this->design_id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'image_url' => $this->product->image_url,
                'min_quantity' => $this->product->min_quantity,
            ],
            'color' => [
                'id' => $this->color->id,
                'name' => $this->color->name,
                'hex_code' => $this->color->hex_code,
            ],
            'print_area' => [
                'id' => $this->printArea->id,
                'name' => $this->printArea->name,
            ],
            'design_prompt' => $this->design_prompt,
            'mockup_url' => $this->mockup_url,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'total_price' => (float) $this->total_price,
            'formatted_total' => $this->formatted_total,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
