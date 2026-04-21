<?php

namespace App\Http\Resources\JobCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardFeedbackResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'comment'     => $this->comment,
            'role'        => $this->role->value,
            'role_label'  => $this->role->label(),
            'version'     => $this->version,
            'is_approved' => $this->is_approved,
            'author'      => $this->whenLoaded('author', fn() => [
                'id'   => $this->author->id,
                'name' => $this->author->name,
            ]),
            'created_at'  => $this->created_at?->toIso8601String(),
        ];
    }
}
