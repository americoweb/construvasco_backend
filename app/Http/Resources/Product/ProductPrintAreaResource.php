<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductPrintAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position->value,
            'position_label' => $this->position->label(),
            'description' => $this->description,
            'max_width_cm' => (float) $this->max_width_cm,
            'max_height_cm' => (float) $this->max_height_cm,
            'dimensions' => $this->dimensions,
            'additional_price' => (float) $this->additional_price,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];
    }
}
