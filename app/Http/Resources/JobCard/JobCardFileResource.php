<?php

namespace App\Http\Resources\JobCard;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobCardFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'type'                 => $this->type->value,
            'type_label'           => $this->type->label(),
            'file_name'            => $this->file_name,
            'file_url'             => $this->file_url,
            'mime_type'            => $this->mime_type,
            'file_size'            => $this->file_size,
            'version'              => $this->version,
            'notes'                => $this->notes,
            // Drive sync status
            'drive_synced'         => (bool) $this->drive_file_id,
            'drive_link'           => $this->drive_link,
            'drive_download_link'  => $this->drive_download_link,
            'drive_synced_at'      => $this->drive_synced_at?->toIso8601String(),
            'uploaded_by'          => $this->whenLoaded('uploader', fn() => [
                'id'   => $this->uploader->id,
                'name' => $this->uploader->name,
            ]),
            'created_at'           => $this->created_at?->toIso8601String(),
        ];
    }
}
