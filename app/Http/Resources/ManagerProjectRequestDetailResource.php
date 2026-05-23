<?php

namespace App\Http\Resources;

use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin ProjectRequest */
class ManagerProjectRequestDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->user;

        return [
            'id' => $this->id,
            'reference_code' => $this->reference_code,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status?->value ?? $this->status,
            'project_type' => $this->project_type,
            'tipologia' => $this->tipologia,
            'localizacao' => $this->localizacao,
            'area_m2' => $this->area_m2,
            'num_pisos' => $this->num_pisos,
            'estilo_arquitectonico' => $this->estilo_arquitectonico,
            'paleta_acabamento' => $this->paleta_acabamento,
            'briefing_data' => $this->briefing_data,
            'approved_ai_generation_id' => $this->approved_ai_generation_id,
            'submitted_at' => $this->submitted_at,
            'reviewed_at' => $this->reviewed_at,
            'converted_project_id' => $this->converted_project_id,
            'whatsapp' => $this->whatsapp,
            'client' => [
                'id' => $user?->id,
                'name' => $user?->name,
                'email' => $user?->email ?? $user?->identifier,
                'phone' => $this->whatsapp ?: null,
            ],
            'user' => $user,
            'quotes' => $this->whenLoaded('quotes', fn () => $this->quotes->map(fn ($q) => [
                'id' => $q->id,
                'quote_type' => $q->quote_type?->value ?? $q->quote_type,
                'status' => $q->status?->value ?? $q->status,
                'total_amount_mt' => $q->total_amount_mt,
                'delivery_days' => $q->delivery_days,
                'conditions' => $q->conditions,
                'sent_at' => $q->sent_at,
                'responded_at' => $q->responded_at,
                'expires_at' => $q->expires_at,
                'rejection_reason' => $q->rejection_reason,
            ])),
            'documents' => $this->whenLoaded('documents'),
            'approved_ai_generation' => $this->whenLoaded(
                'approvedAiGeneration',
                fn () => $this->approvedAiGeneration
                    ? self::formatGeneration($this->approvedAiGeneration)
                    : null
            ),
            'projects' => Project::withoutGlobalScopes()
                ->where('project_request_id', $this->id)
                ->get(['id', 'name', 'contract_phase', 'status', 'created_at', 'updated_at'])
                ->map(fn (Project $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'contract_phase' => $p->contract_phase?->value ?? $p->contract_phase,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                    'updated_at' => $p->updated_at,
                ]),
        ];
    }

    public static function formatGeneration(AiGeneration $gen): array
    {
        $data = $gen->toArray();
        $data['image_url'] = self::resolveImageUrl($gen);

        return $data;
    }

    private static function resolveImageUrl(AiGeneration $gen): ?string
    {
        if ($gen->image_url) {
            return $gen->image_url;
        }
        if ($gen->image_path) {
            return Storage::disk('public')->url($gen->image_path);
        }

        return null;
    }
}
