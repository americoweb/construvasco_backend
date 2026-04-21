<?php

namespace App\Http\Resources\JobCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product'      => $this->product ? [
                'id'   => $this->product->id,
                'name' => $this->product->name,
                'image_url' => $this->product->image_url,
            ] : null,
            'product_color_id' => $this->product_color_id,
            'product_color'    => $this->productColor ? [
                'id'       => $this->productColor->id,
                'name'     => $this->productColor->name,
                'hex_code' => $this->productColor->hex_code,
            ] : null,
            'product_size_id' => $this->product_size_id,
            'product_size'    => $this->productSize ? [
                'id'   => $this->productSize->id,
                'name' => $this->productSize->name,
            ] : null,
            'product_type' => $this->product_type,
            'quantity'     => $this->quantity,
            'size'         => $this->size,
            'material'     => $this->material,
            'notes'        => $this->notes,
            'created_at'   => $this->created_at?->toIso8601String(),
        ];
    }
}
