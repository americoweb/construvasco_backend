<?php

namespace App\Services\Construction;

use App\Enums\ProjectContractPhase;
use App\Enums\ProjectPaymentPhase;
use App\Enums\ProjectPaymentStatus;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Mail\ConstructionCompletedMail;
use App\Mail\ConstructionQuoteRequestedMail;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Mail\EmailDispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConstructionFlowService
{
    public function __construct(
        private PaymentService $payments,
        private EmailDispatcher $emails,
    ) {}

    public function requestConstructionQuote(
        Project $project,
        User $customer,
        ?string $suggestedVisitDate = null,
        ?string $notes = null,
    ): Project {
        abort_unless($project->client_user_id === $customer->id, 403);

        if ($project->contract_phase !== ProjectContractPhase::Architecture) {
            throw ValidationException::withMessages([
                'contract_phase' => ['O projecto não está na fase de arquitectura.'],
            ]);
        }

        if (! $project->architecture_completed_at) {
            throw ValidationException::withMessages([
                'architecture_completed_at' => ['A arquitectura deve estar marcada como entregue.'],
            ]);
        }

        if (! $this->payments->isArchitecturePaymentConfirmed($project)) {
            throw ValidationException::withMessages([
                'payment' => ['O pagamento de arquitectura deve estar confirmado.'],
            ]);
        }

        $hasSentConstructionQuote = Quote::where('project_request_id', $project->project_request_id)
            ->where('quote_type', QuoteType::Construction)
            ->where('status', QuoteStatus::Sent)
            ->exists();

        if ($hasSentConstructionQuote) {
            throw ValidationException::withMessages([
                'quote' => ['Já existe um orçamento de obra em envio para este projecto.'],
            ]);
        }

        if ($project->construction_quote_requested_at) {
            throw ValidationException::withMessages([
                'contract_phase' => ['Já solicitou orçamento de obra para este projecto.'],
            ]);
        }

        return DB::transaction(function () use ($project, $customer, $suggestedVisitDate, $notes) {
            $project->update([
                'contract_phase' => ProjectContractPhase::ExecutionQuote,
                'suggested_site_visit_date' => $suggestedVisitDate,
                'construction_request_notes' => $notes ? trim($notes) : null,
                'construction_quote_requested_at' => now(),
            ]);

            activity('projects')
                ->performedOn($project)
                ->causedBy($customer)
                ->withProperties([
                    'suggested_visit_date' => $suggestedVisitDate,
                    'notes' => $notes,
                ])
                ->log('Cliente solicitou orçamento de obra');

            $this->notifyManagersConstructionQuoteRequested($project->fresh(['client']));

            return $project->fresh();
        });
    }

    public function markConstructionCompleted(Project $project, User $manager): Project
    {
        if ($project->contract_phase !== ProjectContractPhase::Construction) {
            throw ValidationException::withMessages([
                'contract_phase' => ['O projecto não está em fase de construção.'],
            ]);
        }

        $constructionPayment = $this->payments->constructionPaymentForProject($project);
        if (! $constructionPayment || $constructionPayment->status !== ProjectPaymentStatus::Confirmed) {
            throw ValidationException::withMessages([
                'payment' => ['O pagamento de obra deve estar confirmado antes de marcar a obra como concluída.'],
            ]);
        }

        $project->update([
            'contract_phase' => ProjectContractPhase::Completed,
            'construction_completed_at' => now(),
        ]);

        activity('projects')
            ->performedOn($project)
            ->causedBy($manager)
            ->log('Gestor marcou obra concluída');

        if ($project->client) {
            $this->emails->dispatchIdempotent(
                'construction_completed',
                $project->client,
                new ConstructionCompletedMail($project->fresh(['client'])),
                Project::class,
                $project->id,
            );
        }

        return $project->fresh(['client', 'constructionQuote']);
    }

    public function countPendingConstructionQuoteRequests(): int
    {
        return Project::where('contract_phase', ProjectContractPhase::ExecutionQuote)
            ->whereNotNull('construction_quote_requested_at')
            ->whereDoesntHave('projectRequest.quotes', function ($q) {
                $q->where('quote_type', QuoteType::Construction)
                    ->where('status', QuoteStatus::Sent);
            })
            ->count();
    }

    /** @return \Illuminate\Support\Collection<int, Project> */
    public function listPendingConstructionQuoteRequests(int $limit = 5)
    {
        return Project::with(['client'])
            ->where('contract_phase', ProjectContractPhase::ExecutionQuote)
            ->whereNotNull('construction_quote_requested_at')
            ->whereDoesntHave('projectRequest.quotes', function ($q) {
                $q->where('quote_type', QuoteType::Construction)
                    ->where('status', QuoteStatus::Sent);
            })
            ->latest('construction_quote_requested_at')
            ->take($limit)
            ->get();
    }

    private function notifyManagersConstructionQuoteRequested(Project $project): void
    {
        $managers = User::role(['project_manager', 'admin'], 'api')
            ->where('is_active', true)
            ->get();

        foreach ($managers as $manager) {
            $this->emails->dispatchIdempotent(
                'construction_quote_requested',
                $manager,
                new ConstructionQuoteRequestedMail($project),
                Project::class,
                abs(crc32("{$project->id}-mgr-{$manager->id}")),
            );
        }
    }
}
