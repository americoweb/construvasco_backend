<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $parentName = null;
        if ($this->relationLoaded('parent') && $this->parent !== null) {
            $parentName = $this->parent->name;
        }
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'parent_id' => $this->parent_id,
            'parent_name' => $parentName,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
        ];
    }
}

