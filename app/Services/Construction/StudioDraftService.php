<?php

namespace App\Services\Construction;

use App\Enums\AiGenerationStatus;
use App\Enums\ProjectRequestStatus;
use App\Models\AI\AiGeneration;
use App\Models\Construction\ProjectDocument;
use App\Models\Construction\ProjectRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudioDraftService
{
    /**
     * Um único rascunho de estúdio por cliente. Reutiliza o existente ou cria novo.
     */
    public function resolveOrCreateDraft(User $user): ProjectRequest
    {
        $existing = ProjectRequest::where('user_id', $user->id)
            ->where('status', ProjectRequestStatus::Draft)
            ->latest('updated_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return ProjectRequest::create([
            'user_id' => $user->id,
            'reference_code' => $this->nextReference(),
            'title' => 'Novo projecto',
            'status' => ProjectRequestStatus::Draft,
        ]);
    }

    /**
     * Passo 1 considerado preenchido (para diálogo Continuar vs recomeçar).
     */
    public function hasMeaningfulContent(ProjectRequest $request): bool
    {
        $title = trim((string) $request->title);
        if ($title === '' || Str::lower($title) === 'novo projecto') {
            return false;
        }

        return filled($request->project_type)
            || filled($request->tipologia)
            || filled($request->localizacao);
    }

    /**
     * Limpa rascunho e gerações associadas para recomeçar do zero.
     */
    public function resetDraft(ProjectRequest $request): ProjectRequest
    {
        return DB::transaction(function () use ($request) {
            AiGeneration::where('project_request_id', $request->id)->delete();
            ProjectDocument::where('project_request_id', $request->id)->delete();

            $request->update([
                'title' => 'Novo projecto',
                'description' => null,
                'project_type' => null,
                'tipologia' => null,
                'area_m2' => null,
                'num_pisos' => null,
                'localizacao' => null,
                'estilo_arquitectonico' => null,
                'paleta_acabamento' => null,
                'briefing_data' => null,
                'approved_ai_generation_id' => null,
            ]);

            return $request->fresh(['documents', 'aiGenerations', 'approvedAiGeneration']);
        });
    }

    public function generationsForRequest(ProjectRequest $request, bool $includeSuperseded = false)
    {
        $query = AiGeneration::where('project_request_id', $request->id)
            ->orderByDesc('created_at');

        if (! $includeSuperseded) {
            $query->where('status', '!=', AiGenerationStatus::Superseded);
        }

        return $query->get();
    }

    private function nextReference(): string
    {
        $year = now()->year;
        $seq = ProjectRequest::whereYear('created_at', $year)->count() + 1;

        return sprintf('CV-%d-%04d', $year, $seq);
    }
}
