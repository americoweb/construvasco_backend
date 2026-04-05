<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Product\ProductListResource;
use App\Http\Resources\Product\ProductColorResource;
use App\Http\Resources\Product\ProductPrintAreaResource;
use Illuminate\Support\Facades\Storage;

class DesignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get mockup value - prefer URL, then base64 data URL
        // Access mockup_base64 directly from attributes since it's hidden
        $mockup = null;
        if ($this->mockup_url) {
            $mockup = $this->mockup_url;
        } else {
            // Access the hidden mockup_base64 field directly
            $mockupBase64 = $this->resource->getAttribute('mockup_base64');
            if ($mockupBase64) {
                $mockup = 'data:image/png;base64,' . $mockupBase64;
            }
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'prompt' => $this->prompt,
            'mockup_url' => $this->mockup_url,
            'mockup' => $mockup,
            'has_logo' => $this->hasLogo(),
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,
            'has_reference_image' => $this->hasReferenceImage(),
            'reference_image_url' => $this->reference_image_path ? Storage::url($this->reference_image_path) : null,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'is_completed' => $this->isCompleted(),
            'has_mockup' => $this->hasMockup(),
            'generation_attempts' => $this->generation_attempts,
            'ai_model_used' => $this->ai_model_used,
            'is_from_suggestion' => $this->is_from_suggestion,
            'suggestion_id' => $this->suggestion_id,
            'product' => new ProductListResource($this->whenLoaded('product')),
            'color' => new ProductColorResource($this->whenLoaded('color')),
            'print_area' => new ProductPrintAreaResource($this->whenLoaded('printArea')),
            'refinements' => DesignRefinementResource::collection($this->whenLoaded('refinements')),
            'refinements_count' => $this->whenCounted('refinements'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
