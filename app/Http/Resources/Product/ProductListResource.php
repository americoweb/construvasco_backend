<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductListResource extends JsonResource
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
        // Get the first category (primary category) for display
        $primaryCategory = $this->whenLoaded('categories') && $this->categories->isNotEmpty() 
            ? $this->categories->first() 
            : null;
        
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'price' => (float) $this->price,
            'price_formatted' => number_format($this->price, 2, ',', '.') . ' MT',
            'min_quantity' => $this->min_quantity,
            'image_url' => $this->getImageUrl($this->image_url, $request),
            'status' => $this->status->value,
            'is_featured' => $this->is_featured,
            'is_available' => $this->isAvailable(),
            'category' => $primaryCategory ? [
                'id' => $primaryCategory->id,
                'name' => $primaryCategory->name,
                'slug' => $primaryCategory->slug,
            ] : null,
        ];
    }
}
