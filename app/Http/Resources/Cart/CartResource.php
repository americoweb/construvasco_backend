<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'item_count' => $this->items->count(),
            'total_items' => $this->total_items,
            'subtotal' => (float) $this->subtotal,
            'formatted_subtotal' => $this->formatted_subtotal,
            'currency' => 'MT',
            'is_empty' => $this->isEmpty(),
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
