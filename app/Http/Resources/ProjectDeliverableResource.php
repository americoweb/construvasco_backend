<?php

namespace App\Http\Resources;

use App\Models\Construction\ProjectDeliverable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectDeliverable */
class ProjectDeliverableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        if ($status === 'submitted') {
            $status = 'submitted_for_review';
        }

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'title' => $this->title,
            'description' => $this->description,
            'file_name' => $this->original_name ?? basename($this->file_path ?? ''),
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
            'status' => $status,
            'rejection_reason' => $this->rejection_reason,
            'uploaded_by' => $this->whenLoaded('uploader', fn () => [
                'id' => $this->uploader?->id,
                'name' => $this->uploader?->name,
            ]),
            'uploaded_at' => $this->created_at,
            'approved_by' => $this->whenLoaded('approver', fn () => $this->approver ? [
                'id' => $this->approver->id,
                'name' => $this->approver->name,
            ] : null),
            'approved_at' => $this->approved_at,
        ];
    }
}
