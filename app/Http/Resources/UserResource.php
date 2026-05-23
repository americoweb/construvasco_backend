<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        // Get settings properly (handle both array and string formats)
        $settings = $this->resource->getSettingsArray();
        $profileData = $settings['profile'] ?? [];
        
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'identifier' => $this->identifier,
            'type' => $this->type,
            'email' => $this->identifier, // For compatibility - use identifier as email
            'profile_photo_path' => $this->profile_photo_path,
            'avatar_url' => $this->profile_photo_path ? Storage::disk('public')->url($this->profile_photo_path) : null,
            'is_active' => $this->is_active,
            'must_change' => (bool) $this->must_change,
            'verified_at' => $this->verified_at?->toISOString(),
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Profile data from settings
            'phone' => $profileData['phone'] ?? null,
            'whatsapp' => $profileData['whatsapp'] ?? null,
            'company' => $profileData['company'] ?? null,
            'job_title' => $profileData['job_title'] ?? null,
            'bio' => $profileData['bio'] ?? null,
            'settings' => $settings,
            
            'tenant_role' => $this->when(isset($this->tenant_role), function () {
                return [
                    'id' => $this->tenant_role->id,
                    'name' => $this->tenant_role->name,
                    'display_name' => $this->tenant_role->display_name,
                ];
            }),
            'custom_permissions' => $this->when(isset($this->custom_permissions), $this->custom_permissions),
            'raw_custom_permissions' => $this->when(isset($this->raw_custom_permissions), $this->raw_custom_permissions),
            'tenant_status' => $this->when(isset($this->tenant_status), $this->tenant_status),
            'joined_at' => $this->when(isset($this->joined_at), $this->joined_at),
            
            'current_tenant_context' => $this->when($request->user()?->id === $this->id, function () {
                return $this->getTenantContext();
            }),
        ];
    }
}