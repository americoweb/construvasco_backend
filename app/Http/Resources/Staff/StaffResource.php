<?php

namespace App\Http\Resources\Staff;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $role = $this->roles->first();

        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'identifier'        => $this->identifier,
            'role'              => $role ? [
                'name'         => $role->name,
                'display_name' => $role->display_name ?? ucfirst($role->name),
            ] : null,
            'is_active'         => (bool) $this->is_active,
            'profile_photo_path'=> $this->profile_photo_path,
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
