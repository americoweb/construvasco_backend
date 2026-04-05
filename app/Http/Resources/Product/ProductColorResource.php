<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hex_code' => $this->hex_code,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'stock_quantity' => $this->stock_quantity,
            'has_stock' => $this->hasStock(),
        ];
    }
}
