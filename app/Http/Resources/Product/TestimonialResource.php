<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TestimonialResource extends JsonResource
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
            'product_id' => $this->product_id,
            'photo_url' => $this->getImageUrl($this->photo_url, $request),
            'text' => $this->text,
            'author' => $this->author,
            // Frontend compatibility fields
            'client_name' => $this->author,
            'client_photo' => $this->getImageUrl($this->photo_url, $request),
            'comment' => $this->text,
            'rating' => 5, // Default rating since it's not stored in database
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'product' => new ProductResource($this->whenLoaded('product')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

