<?php

namespace App\Http\Resources\Design;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DesignListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'prompt' => $this->prompt,
            'mockup' => $this->mockup,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_completed' => $this->isCompleted(),
            'product_name' => $this->product->name ?? null,
            'color_name' => $this->color->name ?? null,
            'print_area_name' => $this->printArea->name ?? null,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
