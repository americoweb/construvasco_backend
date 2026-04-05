<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'products' => array_map(fn($p) => [
                'name' => $p->name,
                'design_prompt' => $p->designPrompt,
                'theme' => $p->theme,
                'text_to_print' => $p->textToPrint,
                'color_style' => $p->colorStyle,
                'price' => $p->price,
                'quantity' => $p->quantity,
                'total_price' => $p->getTotalPrice(),
                'formatted_price' => number_format($p->price, 2, ',', '.') . ' MT',
                'formatted_total' => number_format($p->getTotalPrice(), 2, ',', '.') . ' MT',
                'mockup_url' => $p->mockupUrl,
                'product_id' => $p->productId,
            ], $this->resource->products),
            'estimated_total' => $this->resource->estimatedTotal,
            'formatted_total' => number_format($this->resource->estimatedTotal, 2, ',', '.') . ' MT',
            'cta' => $this->resource->cta,
            'bundle_theme' => $this->resource->bundleTheme,
            'product_count' => $this->resource->getProductCount(),
            'is_bundle' => $this->resource->isBundle(),
        ];
    }
}
