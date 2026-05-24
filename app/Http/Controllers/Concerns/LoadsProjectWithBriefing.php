<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Resources\ManagerProjectRequestDetailResource;
use App\Http\Resources\ProjectDeliverableResource;
use App\Http\Resources\ProjectPaymentResource;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use App\Enums\ProjectPaymentPhase;

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

        $architecturePayment = $project->payments
            ->first(fn ($p) => ($p->phase?->value ?? $p->phase) === ProjectPaymentPhase::Architecture->value)
            ?? ProjectPayment::where('project_id', $project->id)
                ->where('phase', ProjectPaymentPhase::Architecture)
                ->with(['confirmedByUser', 'user'])
                ->latest('id')
                ->first();

        if ($architecturePayment) {
            $data['payment'] = (new ProjectPaymentResource($architecturePayment))->resolve();
        }

        return $data;
    }
}
