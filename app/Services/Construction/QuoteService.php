<?php

namespace App\Services\Construction;

use App\Constants\NotificationTypes;
use App\Enums\ProjectContractPhase;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Mail\QuoteAcceptedMail;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\ProjectTemplate;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Mail\EmailDispatcher;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(
        private ProjectTemplateService $templateService,
        private NotificationService $notifications,
        private EmailDispatcher $emails,
    ) {}

    public function assertCanCreateQuote(ProjectRequest $request, QuoteType $type): void
    {
        $hasSentOfType = Quote::where('project_request_id', $request->id)
            ->where('quote_type', $type->value)
            ->where('status', QuoteStatus::Sent)
            ->exists();

        if ($hasSentOfType) {
            $label = $type === QuoteType::Architecture ? 'arquitectura' : 'obra';
            throw ValidationException::withMessages([
                'quote_type' => ["Já existe um orçamento de {$label} em envio para este pedido."],
            ]);
        }
    }

    public function assertRequestAllowsQuote(ProjectRequest $request): void
    {
        $blocked = [
            ProjectRequestStatus::Draft,
            ProjectRequestStatus::Rejected,
            ProjectRequestStatus::Cancelled,
            ProjectRequestStatus::Closed,
            ProjectRequestStatus::ConvertedToProject,
        ];

        if (in_array($request->status, $blocked, true)) {
            throw ValidationException::withMessages([
                'status' => ['O estado actual do pedido não permite enviar orçamento.'],
            ]);
        }

        $allowed = [
            ProjectRequestStatus::Submitted,
            ProjectRequestStatus::UnderReview,
            ProjectRequestStatus::Quoted,
        ];

        if (! in_array($request->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['O estado actual do pedido não permite enviar orçamento.'],
            ]);
        }
    }

    public function assertQuoteRespondable(Quote $quote): void
    {
        if ($quote->status !== QuoteStatus::Sent) {
            throw ValidationException::withMessages([
                'quote' => ['Este orçamento já não está disponível para resposta.'],
            ]);
        }
    }

    public function accept(Quote $quote, User $customer): Project
    {
        return DB::transaction(function () use ($quote, $customer) {
            $quote->refresh();
            $request = $quote->projectRequest;
            abort_unless($request->user_id === $customer->id, 403);
            $this->assertQuoteRespondable($quote);

            $quoteType = $quote->quote_type ?? QuoteType::Architecture;

            if ($quoteType === QuoteType::Architecture) {
                return $this->acceptArchitectureQuote($quote, $customer, $request);
            }

            return $this->acceptConstructionQuote($quote, $customer, $request);
        });
    }

    public function reject(Quote $quote, User $customer, ?string $reason = null): Quote
    {
        $request = $quote->projectRequest;
        abort_unless($request->user_id === $customer->id, 403);
        $this->assertQuoteRespondable($quote);

        $quoteType = $quote->quote_type ?? QuoteType::Architecture;

        $quote->update([
            'status' => QuoteStatus::Rejected,
            'responded_at' => now(),
            'rejection_reason' => $reason,
        ]);

        if ($quoteType === QuoteType::Architecture) {
            activity('quotes')
                ->performedOn($quote)
                ->causedBy($customer)
                ->withProperties(['rejection_reason' => $reason])
                ->log('Orçamento de arquitectura recusado pelo cliente');
        }

        if ($quoteType === QuoteType::Construction) {
            $project = Project::withoutGlobalScopes()
                ->where('project_request_id', $request->id)
                ->first();

            if ($project) {
                $project->update(['contract_phase' => ProjectContractPhase::Closed]);
            }
            $request->update(['status' => ProjectRequestStatus::Closed]);
        } else {
            $request->update(['status' => ProjectRequestStatus::UnderReview]);
        }

        $this->notifications->notify($quote->createdBy, NotificationTypes::QUOTE_REJECTED, [
            'title' => 'Orçamento recusado',
            'message' => 'O cliente recusou o orçamento enviado.',
            'reference_type' => Quote::class,
            'reference_id' => $quote->id,
        ]);

        return $quote->fresh();
    }

    private function acceptArchitectureQuote(Quote $quote, User $customer, ProjectRequest $request): Project
    {
        $quote->update([
            'status' => QuoteStatus::Accepted,
            'responded_at' => now(),
        ]);

        activity('quotes')
            ->performedOn($quote)
            ->causedBy($customer)
            ->log('Orçamento de arquitectura aceite pelo cliente');

        $request->update(['status' => ProjectRequestStatus::Approved]);

        $project = Project::create([
            'tenant_id' => session('tenant_id', 1),
            'client_user_id' => $customer->id,
            'project_request_id' => $request->id,
            'quote_id' => $quote->id,
            'project_template_id' => $quote->project_template_id,
            'name' => $request->title,
            'description' => $request->description,
            'status' => 'active',
            'current_phase' => 'active',
            'contract_phase' => ProjectContractPhase::Architecture,
            'project_type' => $request->project_type,
            'location' => $request->localizacao,
            'target_budget' => $quote->total_amount_mt,
            'desired_deadline' => $request->prazo_desejado,
            'final_payment_status' => 'pending',
            'client_can_download' => false,
        ]);

        if ($quote->project_template_id) {
            $template = ProjectTemplate::with('phases')->find($quote->project_template_id);
            if ($template) {
                $this->templateService->instantiateMilestones($project, $template);
            }
        }

        $request->update([
            'status' => ProjectRequestStatus::ConvertedToProject,
            'converted_project_id' => $project->id,
        ]);

        $this->notifications->notify($customer, NotificationTypes::PROJECT_STARTED, [
            'title' => 'Projecto iniciado',
            'message' => "O seu projecto «{$project->name}» foi criado.",
            'reference_type' => Project::class,
            'reference_id' => $project->id,
        ]);

        if ($quote->createdBy) {
            $this->emails->dispatchIdempotent(
                'quote_accepted',
                $quote->createdBy,
                new QuoteAcceptedMail($quote->fresh(['projectRequest']), $project),
                Quote::class,
                $quote->id,
            );
        }

        return $project->load('milestones');
    }

    private function acceptConstructionQuote(Quote $quote, User $customer, ProjectRequest $request): Project
    {
        $project = Project::withoutGlobalScopes()
            ->where('project_request_id', $request->id)
            ->first();

        if (! $project) {
            throw ValidationException::withMessages([
                'quote' => ['Não existe projecto associado a este pedido.'],
            ]);
        }

        if ($project->contract_phase !== ProjectContractPhase::ExecutionQuote) {
            throw ValidationException::withMessages([
                'quote' => ['O projecto deve estar na fase de orçamento de obra para aceitar este orçamento.'],
            ]);
        }

        if (! $project->architecture_completed_at) {
            throw ValidationException::withMessages([
                'quote' => ['A fase de arquitectura deve estar concluída antes de aceitar o orçamento de obra.'],
            ]);
        }

        $quote->update([
            'status' => QuoteStatus::Accepted,
            'responded_at' => now(),
        ]);

        $project->update([
            'contract_phase' => ProjectContractPhase::Construction,
            'construction_quote_id' => $quote->id,
            'target_budget' => $quote->total_amount_mt,
        ]);

        $request->update(['status' => ProjectRequestStatus::ConvertedToProject]);

        if ($quote->createdBy) {
            $this->emails->dispatchIdempotent(
                'quote_accepted',
                $quote->createdBy,
                new QuoteAcceptedMail($quote->fresh(['projectRequest']), $project->fresh()),
                Quote::class,
                $quote->id,
            );
        }

        return $project->fresh(['milestones']);
    }
}
