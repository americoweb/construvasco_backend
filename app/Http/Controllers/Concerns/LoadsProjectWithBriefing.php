<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\ProjectContractPhase;
use App\Enums\QuoteStatus;
use App\Enums\QuoteType;
use App\Http\Resources\ManagerProjectRequestDetailResource;
use App\Http\Resources\ProjectDeliverableResource;
use App\Http\Resources\ProjectPaymentResource;
use App\Http\Resources\QuoteResource;
use App\Models\Construction\ProjectPayment;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Enums\ProjectPaymentPhase;
use App\Support\ProjectContractPhaseLabel;

trait LoadsProjectWithBriefing
{
    protected function projectWithBriefing(int $id): Project
    {
        return Project::with([
            'client',
            'milestones',
            'deliverables.uploader',
            'deliverables.approver',
            'assignments.assignedUser',
            'projectRequest.approvedAiGeneration',
            'projectRequest.documents',
            'constructionQuote',
            'payments.confirmedByUser',
            'payments.user',
        ])->findOrFail($id);
    }

    /** @return array<string, mixed> */
    protected function projectPayload(Project $project): array
    {
        $data = $project->toArray();
        $request = $project->projectRequest;

        if ($request) {
            $data['project_request'] = (new ManagerProjectRequestDetailResource($request))->resolve();
        }

        if ($project->relationLoaded('deliverables')) {
            $data['deliverables'] = ProjectDeliverableResource::collection($project->deliverables)->resolve();
        }

        $phase = $project->contract_phase;
        $data['contract_phase_label'] = ProjectContractPhaseLabel::for($phase);

        $architecturePayment = $this->paymentForPhase($project, ProjectPaymentPhase::Architecture);
        if ($architecturePayment) {
            $arch = (new ProjectPaymentResource($architecturePayment))->resolve();
            $data['architecture_payment'] = $arch;
            $data['payment'] = $arch;
        }

        $constructionPayment = $this->paymentForPhase($project, ProjectPaymentPhase::Construction);
        if ($constructionPayment) {
            $data['construction_payment'] = (new ProjectPaymentResource($constructionPayment))->resolve();
        }

        if ($project->constructionQuote) {
            $data['construction_quote'] = (new QuoteResource($project->constructionQuote))->resolve();
        } elseif ($project->project_request_id) {
            $pendingConstructionQuote = Quote::where('project_request_id', $project->project_request_id)
                ->where('quote_type', QuoteType::Construction)
                ->where('status', QuoteStatus::Sent)
                ->latest('id')
                ->first();
            if ($pendingConstructionQuote) {
                $data['construction_quote'] = (new QuoteResource($pendingConstructionQuote))->resolve();
            }
        }

        $data['suggested_visit_date'] = $project->suggested_site_visit_date?->format('Y-m-d');

        return $data;
    }

    private function paymentForPhase(Project $project, ProjectPaymentPhase $phase): ?ProjectPayment
    {
        $loaded = $project->payments
            ->first(fn ($p) => ($p->phase?->value ?? $p->phase) === $phase->value);

        if ($loaded) {
            return $loaded;
        }

        return ProjectPayment::where('project_id', $project->id)
            ->where('phase', $phase)
            ->with(['confirmedByUser', 'user'])
            ->latest('id')
            ->first();
    }
}
