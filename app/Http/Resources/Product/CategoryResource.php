<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    protected function getImageUrl(?string $path, Request $request): ?string
    {
        if (!$path) {
            return null;
        }
        
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        $baseUrl = $request->getSchemeAndHttpHost();
        return rtrim($baseUrl, '/') . '/storage/' . ltrim($path, '/');
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->getImageUrl($this->image_url, $request),
            'parent_id' => $this->parent_id,
            'parent' => new CategoryResource($this->whenLoaded('parent')),
            'children' => CategoryResource::collection($this->whenLoaded('children')),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

