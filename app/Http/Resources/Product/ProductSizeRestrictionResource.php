<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSizeRestrictionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'min_width_cm' => $this->min_width_cm ? (float) $this->min_width_cm : null,
            'max_width_cm' => $this->max_width_cm ? (float) $this->max_width_cm : null,
            'min_height_cm' => $this->min_height_cm ? (float) $this->min_height_cm : null,
            'max_height_cm' => $this->max_height_cm ? (float) $this->max_height_cm : null,
            'min_aspect_ratio' => $this->min_aspect_ratio ? (float) $this->min_aspect_ratio : null,
            'max_aspect_ratio' => $this->max_aspect_ratio ? (float) $this->max_aspect_ratio : null,
            'step_increment_cm' => $this->step_increment_cm ? (float) $this->step_increment_cm : null,
        ];
    }
}

