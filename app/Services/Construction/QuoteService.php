<?php

namespace App\Services\Construction;

use App\Constants\NotificationTypes;
use App\Enums\ProjectRequestStatus;
use App\Enums\QuoteStatus;
use App\Models\Construction\ProjectRequest;
use App\Models\Construction\ProjectTemplate;
use App\Models\Construction\Quote;
use App\Models\Project;
use App\Models\User;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;

class QuoteService
{
    public function __construct(
        private ProjectTemplateService $templateService,
        private NotificationService $notifications
    ) {}

    public function accept(Quote $quote, User $customer): Project
    {
        return DB::transaction(function () use ($quote, $customer) {
            $request = $quote->projectRequest;
            abort_unless($request->user_id === $customer->id, 403);

            $quote->update([
                'status' => QuoteStatus::Accepted,
                'responded_at' => now(),
            ]);

            $request->update([
                'status' => ProjectRequestStatus::Approved,
            ]);

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

            return $project->load('milestones');
        });
    }

    public function reject(Quote $quote, User $customer, ?string $reason = null): Quote
    {
        $request = $quote->projectRequest;
        abort_unless($request->user_id === $customer->id, 403);

        $quote->update([
            'status' => QuoteStatus::Rejected,
            'responded_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $request->update(['status' => ProjectRequestStatus::Rejected]);

        $this->notifications->notify($quote->createdBy, NotificationTypes::QUOTE_REJECTED, [
            'title' => 'Orçamento recusado',
            'message' => 'O cliente recusou o orçamento enviado.',
            'reference_type' => Quote::class,
            'reference_id' => $quote->id,
        ]);

        return $quote->fresh();
    }
}
