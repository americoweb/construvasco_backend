<?php

namespace App\Http\Resources\JobCard;

use App\Http\Resources\Order\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'uuid'                  => $this->uuid,
            'job_number'            => $this->job_number,

            // Status
            'status'                => $this->status->value,
            'status_label'          => $this->status->label(),
            'status_color'          => $this->status->color(),

            // Brief
            'title'                 => $this->title,
            'description'           => $this->description,
            'objective'             => $this->objective,
            'deadline'              => $this->deadline?->toIso8601String(),
            'is_overdue'            => $this->isOverdue(),
            'notes'                 => $this->notes,

            // Priority
            'priority'              => $this->priority->value,
            'priority_label'        => $this->priority->label(),
            'priority_color'        => $this->priority->color(),
            'priority_override'     => $this->priority_override,
            'priority_reason'       => $this->priority_reason,
            'priority_score'        => $this->priority_score,

            // Client tier
            'client_tier'           => $this->client_tier->value,
            'client_tier_label'     => $this->client_tier->label(),

            // Revisions
            'revision_limit'        => $this->revision_limit,
            'revision_count'        => $this->revision_count,
            'over_revision_limit'   => $this->isOverRevisionLimit(),
            'can_be_cancelled'      => $this->canBeCancelled(),

            // People
            'client'                => $this->whenLoaded('client', fn() => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
                'email'=> $this->client->email,
            ]),
            'creator'               => $this->whenLoaded('creator', fn() => [
                'id'   => $this->creator->id,
                'name' => $this->creator->name,
            ]),
            'designer'              => $this->whenLoaded('designer', fn() => [
                'id'   => $this->designer->id,
                'name' => $this->designer->name,
            ]),

            // Order link
            'order_id'              => $this->order_id,
            'order'                 => $this->when(
                $this->relationLoaded('order') && $this->order !== null,
                fn () => new OrderResource($this->order)
            ),

            // Relations
            'items'    => JobCardItemResource::collection($this->whenLoaded('items')),
            'files'    => JobCardFileResource::collection($this->whenLoaded('files')),
            'feedback' => JobCardFeedbackResource::collection($this->whenLoaded('feedback')),

            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
