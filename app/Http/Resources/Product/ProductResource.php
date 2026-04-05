<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Generate full URL from relative path stored in database
     */
    protected function getImageUrl(?string $path, Request $request): ?string
    {
        if (!$path) {
            return null;
        }
        
        // If it's already a full URL (legacy data), return as is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        
        // Generate full URL using request's base URL
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
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2, ',', '.') . ' MT',
            'min_quantity' => $this->min_quantity,
            'image_url' => $this->getImageUrl($this->image_url, $request),
            'base_image_url' => $this->getImageUrl($this->base_image_url, $request),
            'design_hint' => $this->design_hint,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_featured' => $this->is_featured,
            'is_available' => $this->isAvailable(),
            'sort_order' => $this->sort_order,
            'pricing_type' => $this->pricing_type?->value,
            'price_per_sqm' => $this->price_per_sqm ? (float) $this->price_per_sqm : null,
            'has_sizes' => $this->has_sizes ?? false,
            'colors' => ProductColorResource::collection($this->whenLoaded('colors')),
            'active_colors' => ProductColorResource::collection($this->whenLoaded('activeColors')),
            'print_areas' => ProductPrintAreaResource::collection($this->whenLoaded('printAreas')),
            'active_print_areas' => ProductPrintAreaResource::collection($this->whenLoaded('activePrintAreas')),
            'sizes' => ProductSizeResource::collection($this->whenLoaded('sizes')),
            'active_sizes' => ProductSizeResource::collection($this->whenLoaded('activeSizes')),
            'size_restrictions' => $this->when(
                $this->relationLoaded('sizeRestrictions') && $this->sizeRestrictions->isNotEmpty(),
                function () {
                    return new ProductSizeRestrictionResource($this->sizeRestrictions->first());
                }
            ),
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'tags' => TagResource::collection($this->whenLoaded('tags')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
