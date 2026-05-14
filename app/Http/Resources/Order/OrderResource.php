<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'payment_status_color' => $this->payment_status->color(),
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'total_items' => $this->total_items,
            'shipping' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
                'phone' => $this->shipping_phone,
                'whatsapp' => $this->shipping_whatsapp,
            ],
            'billing' => [
                'name' => $this->billing_name,
                'email' => $this->billing_email,
            ],
            'construction_briefing' => [
                'project_type' => $this->project_type,
                'service_type' => $this->service_type,
                'terrain_area_sqm' => $this->terrain_area_sqm !== null ? (float) $this->terrain_area_sqm : null,
                'terrain_location' => $this->terrain_location,
                'terrain_type' => $this->terrain_type,
                'budget_target' => $this->budget_target !== null ? (float) $this->budget_target : null,
                'desired_deadline' => $this->desired_deadline?->toDateString(),
                'style_preferences' => $this->style_preferences,
                'floors_count' => $this->floors_count,
                'rooms_count' => $this->rooms_count,
                'technical_requirements' => $this->technical_requirements ?? [],
                'metadata' => $this->briefing_metadata ?? [],
                'attachments' => $this->briefing_attachments ?? [],
            ],
            'notes' => $this->notes,
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_pending' => $this->isPending(),
            'is_delivered' => $this->isDelivered(),
            'is_cancelled' => $this->isCancelled(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'job_card_id' => $this->whenLoaded('jobCard', fn() => $this->jobCard?->id),
            'job_card_number' => $this->whenLoaded('jobCard', fn() => $this->jobCard?->job_number),
            'job_card_status' => $this->whenLoaded('jobCard', fn() => $this->jobCard?->status?->value),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
