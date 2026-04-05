<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSizeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'width_cm' => $this->width_cm ? (float) $this->width_cm : null,
            'height_cm' => $this->height_cm ? (float) $this->height_cm : null,
            'is_predefined' => $this->is_predefined,
            'is_custom' => $this->is_custom,
            'fixed_price' => $this->fixed_price ? (float) $this->fixed_price : null,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'area_sqm' => $this->area_sqm ? (float) $this->area_sqm : null,
            'dimensions' => $this->dimensions,
        ];
    }
}

