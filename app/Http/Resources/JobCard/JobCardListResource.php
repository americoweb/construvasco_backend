<?php

namespace App\Http\Resources\JobCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'job_number'        => $this->job_number,

            'status'            => $this->status->value,
            'status_label'      => $this->status->label(),
            'status_color'      => $this->status->color(),

            'title'             => $this->title,
            'deadline'          => $this->deadline?->toIso8601String(),
            'is_overdue'        => $this->isOverdue(),

            'priority'          => $this->priority->value,
            'priority_label'    => $this->priority->label(),
            'priority_color'    => $this->priority->color(),
            'priority_override' => $this->priority_override,
            'priority_score'    => $this->priority_score,

            'client_tier'       => $this->client_tier->value,
            'client_tier_label' => $this->client_tier->label(),

            'revision_count'    => $this->revision_count,
            'revision_limit'    => $this->revision_limit,
            'can_be_cancelled'  => $this->canBeCancelled(),

            'client'            => $this->whenLoaded('client', fn() => [
                'id'   => $this->client->id,
                'name' => $this->client->name,
            ]),
            'designer'          => $this->whenLoaded('designer', fn() => [
                'id'   => $this->designer->id,
                'name' => $this->designer->name,
            ]),

            'created_at'        => $this->created_at->toIso8601String(),
        ];
    }
}
