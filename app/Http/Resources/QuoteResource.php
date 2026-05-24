<?php

namespace App\Http\Resources;

use App\Models\Construction\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Quote */
class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        $quoteType = $this->quote_type;
        if ($quoteType instanceof \BackedEnum) {
            $quoteType = $quoteType->value;
        }

        return [
            'id' => $this->id,
            'project_request_id' => $this->project_request_id,
            'quote_type' => $quoteType,
            'total_amount_mt' => $this->total_amount_mt,
            'delivery_days' => $this->delivery_days,
            'conditions' => $this->conditions,
            'status' => $status,
            'sent_at' => $this->sent_at,
            'responded_at' => $this->responded_at,
            'expires_at' => $this->expires_at,
            'rejection_reason' => $this->rejection_reason,
        ];
    }
}
