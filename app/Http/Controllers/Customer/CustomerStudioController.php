<?php

namespace App\Http\Controllers\Customer;

use App\Enums\ProjectRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectRequest;
use App\Services\Construction\StudioDraftService;
use App\Services\Credits\CreditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerStudioController extends Controller
{
    public function __construct(
        private StudioDraftService $studio,
        private CreditService $credits,
    ) {}

    /**
     * Estado agregado do estúdio (arranque em 1 request).
     */
    public function state(Request $request): JsonResponse
    {
        $user = $request->user();
        $draft = $this->studio->resolveOrCreateDraft($user);

        $draft->load(['documents', 'approvedAiGeneration']);

        $generations = $this->studio->generationsForRequest($draft, true);

        return response()->json([
            'data' => [
                'draft' => $draft,
                'has_meaningful_content' => $this->studio->hasMeaningfulContent($draft),
                'credits_balance' => $this->credits->getBalance($user),
                'cost_per_generation' => (int) config('credits.cost_per_generation', 1),
                'generations' => $generations,
                'disclaimer' => 'Os mockups são referências visuais para a Construvasco compreender o que pretende. O projecto técnico (plantas, peças desenhadas, memória descritiva) é elaborado pela nossa equipa após aceitar o orçamento.',
            ],
        ]);
    }

    /**
     * Força criação de novo rascunho vazio (após "Recomeçar" no diálogo).
     */
    public function reset(Request $request): JsonResponse
    {
        $user = $request->user();
        $draft = ProjectRequest::where('user_id', $user->id)
            ->where('status', ProjectRequestStatus::Draft)
            ->latest('updated_at')
            ->first();

        if (! $draft) {
            $draft = $this->studio->resolveOrCreateDraft($user);
        } else {
            $this->authorize('update', $draft);
            $draft = $this->studio->resetDraft($draft);
        }

        return response()->json([
            'data' => [
                'draft' => $draft,
                'has_meaningful_content' => false,
                'credits_balance' => $this->credits->getBalance($user),
                'cost_per_generation' => (int) config('credits.cost_per_generation', 1),
                'generations' => [],
            ],
        ]);
    }
}
