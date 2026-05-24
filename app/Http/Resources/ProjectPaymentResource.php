<?php

namespace App\Http\Resources;

use App\Models\Construction\ProjectPayment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ProjectPayment */
class ProjectPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        $phase = $this->phase;
        if ($phase instanceof \BackedEnum) {
            $phase = $phase->value;
        }

        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $status,
            'phase' => $phase,
            'notes' => $this->notes,
            'proof_path' => $this->when(
                $request->user()?->hasRole(['admin', 'project_manager'], 'api'),
                $this->proof_path
            ),
            'proof_file_name' => $this->proof_path ? basename($this->proof_path) : null,
            'proof_uploaded_at' => $this->proof_uploaded_at,
            'confirmed_at' => $this->confirmed_at,
            'rejected_reason' => $this->rejected_reason,
            'confirmed_by' => $this->whenLoaded('confirmedByUser', fn () => $this->confirmedByUser ? [
                'id' => $this->confirmedByUser->id,
                'name' => $this->confirmedByUser->name,
            ] : null),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'identifier' => $this->user?->identifier,
            ]),
            'project' => $this->whenLoaded('project', fn () => [
                'id' => $this->project->id,
                'name' => $this->project->name,
                'client' => $this->project->relationLoaded('client') && $this->project->client ? [
                    'id' => $this->project->client->id,
                    'name' => $this->project->client->name,
                ] : null,
            ]),
        ];
    }
}
