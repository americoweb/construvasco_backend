<?php

namespace App\Services\Construction;

use App\Constants\NotificationTypes;
use App\Enums\DeliverableStatus;
use App\Enums\ProjectContractPhase;
use App\Mail\ArchitectureCompletedMail;
use App\Mail\DeliverableAvailableMail;
use App\Models\Construction\ProjectAssignment;
use App\Models\Construction\ProjectDeliverable;
use App\Models\Project;
use App\Models\User;
use App\Services\Mail\EmailDispatcher;
use App\Services\Notifications\NotificationService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class DeliverableService
{
    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'application/acad',
        'application/x-acad',
        'application/autocad_dwg',
        'image/vnd.dwg',
        'application/octet-stream',
    ];

    private const MAX_KB = 51200;

    public function __construct(
        private FileStorageService $storage,
        private NotificationService $notifications,
        private EmailDispatcher $emails,
        private PaymentService $payments,
    ) {}

    public function upload(
        Project $project,
        User $technician,
        UploadedFile $file,
        string $title,
        ?string $description = null,
        ?int $milestoneId = null,
    ): ProjectDeliverable {
        $this->assertTechnicianAssigned($project, $technician);
        $this->assertProjectAllowsUpload($project);

        $this->storage->validateFile($file, self::ALLOWED_MIMES, self::MAX_KB);

        $stored = $this->storage->storePrivateFile($file, "projects/{$project->id}/deliverables");

        $deliverable = ProjectDeliverable::create([
            'project_id' => $project->id,
            'project_milestone_id' => $milestoneId,
            'uploaded_by' => $technician->id,
            'deliverable_type' => 'arquitetura',
            'title' => $title,
            'description' => $description,
            'file_path' => $stored['path'],
            'file_disk' => $stored['disk'],
            'file_format' => $file->getClientOriginalExtension(),
            'mime_type' => $stored['mime'],
            'size_bytes' => $stored['size'],
            'original_name' => $stored['original_name'],
            'status' => DeliverableStatus::SubmittedForReview->value,
        ]);

        activity('deliverables')
            ->performedOn($deliverable)
            ->causedBy($technician)
            ->withProperties(['title' => $title])
            ->log("Técnico {$technician->name} submeteu entregável «{$title}» para revisão");

        return $deliverable->load(['uploader', 'approver']);
    }

    public function listForTechnician(Project $project, User $technician): Collection
    {
        $this->assertTechnicianAssigned($project, $technician);

        return $this->baseQuery($project)->get();
    }

    public function listForManager(Project $project): Collection
    {
        return $this->baseQuery($project)->get();
    }

    public function listForCustomer(Project $project): Collection
    {
        return $this->baseQuery($project)
            ->where('status', DeliverableStatus::Approved->value)
            ->get();
    }

    public function approve(Project $project, ProjectDeliverable $deliverable, User $manager): ProjectDeliverable
    {
        $this->assertDeliverableOnProject($project, $deliverable);
        $this->assertStatus($deliverable, DeliverableStatus::pendingReviewValues());

        $deliverable->update([
            'status' => DeliverableStatus::Approved->value,
            'approved_by' => $manager->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        activity('deliverables')
            ->performedOn($deliverable)
            ->causedBy($manager)
            ->log("Gestor {$manager->name} aprovou entregável «{$deliverable->title}»");

        $project->load('client');
        if ($project->client) {
            $this->notifications->notify($project->client, NotificationTypes::DELIVERABLES_AVAILABLE, [
                'title' => 'Entregável disponível',
                'message' => "O entregável «{$deliverable->title}» está disponível para descarregar.",
                'reference_type' => ProjectDeliverable::class,
                'reference_id' => $deliverable->id,
            ]);

            $this->emails->dispatchIdempotent(
                'deliverable_available',
                $project->client,
                new DeliverableAvailableMail($deliverable->fresh()),
                ProjectDeliverable::class,
                $deliverable->id,
            );
        }

        return $deliverable->fresh(['uploader', 'approver']);
    }

    public function reject(
        Project $project,
        ProjectDeliverable $deliverable,
        User $manager,
        string $reason,
    ): ProjectDeliverable {
        $this->assertDeliverableOnProject($project, $deliverable);
        $this->assertStatus($deliverable, DeliverableStatus::pendingReviewValues());

        $deliverable->update([
            'status' => DeliverableStatus::Rejected->value,
            'rejection_reason' => $reason,
            'approved_by' => null,
            'approved_at' => null,
        ]);

        activity('deliverables')
            ->performedOn($deliverable)
            ->causedBy($manager)
            ->withProperties(['rejection_reason' => $reason])
            ->log("Gestor {$manager->name} rejeitou entregável «{$deliverable->title}»");

        $deliverable->load('uploader');
        if ($deliverable->uploader) {
            $this->notifications->notify($deliverable->uploader, NotificationTypes::DELIVERABLES_AVAILABLE, [
                'title' => 'Entregável rejeitado',
                'message' => "O entregável «{$deliverable->title}» foi rejeitado. Motivo: {$reason}",
                'reference_type' => ProjectDeliverable::class,
                'reference_id' => $deliverable->id,
            ]);
        }

        return $deliverable->fresh(['uploader', 'approver']);
    }

    public function markArchitectureDelivered(Project $project, User $manager): Project
    {
        if ($project->contract_phase !== ProjectContractPhase::Architecture) {
            throw ValidationException::withMessages([
                'contract_phase' => ['O projecto não está na fase de arquitectura.'],
            ]);
        }

        if ($project->architecture_completed_at) {
            throw ValidationException::withMessages([
                'architecture_completed_at' => ['A arquitectura já foi marcada como entregue.'],
            ]);
        }

        $hasApproved = $project->deliverables()
            ->where('status', DeliverableStatus::Approved->value)
            ->exists();

        if (! $hasApproved) {
            throw ValidationException::withMessages([
                'deliverables' => ['É necessário pelo menos um entregável aprovado.'],
            ]);
        }

        $project->update(['architecture_completed_at' => now()]);

        activity('projects')
            ->performedOn($project)
            ->causedBy($manager)
            ->log("Gestor {$manager->name} marcou arquitectura entregue");

        $project->load('client');
        if ($project->client) {
            $this->emails->dispatchIdempotent(
                'architecture_completed',
                $project->client,
                new ArchitectureCompletedMail($project->fresh()),
                Project::class,
                $project->id,
            );
        }

        return $project->fresh();
    }

    public function authorizeDownload(
        Project $project,
        ProjectDeliverable $deliverable,
        User $user,
        string $role,
    ): void {
        $this->assertDeliverableOnProject($project, $deliverable);
        $role = strtolower($role);

        if (in_array($role, ['admin', 'project_manager'], true)) {
            return;
        }

        if (in_array($role, ['technician'], true)) {
            abort_unless($deliverable->uploaded_by === $user->id, 403);

            return;
        }

        if ($role === 'customer') {
            abort_unless($project->client_user_id === $user->id, 403);
            abort_unless($deliverable->status === DeliverableStatus::Approved->value, 403, 'Entregável ainda não aprovado.');
            abort_unless(
                $this->payments->isArchitecturePaymentConfirmed($project),
                403,
                'Pagamento da fase de arquitectura ainda não confirmado.'
            );

            activity('deliverables')
                ->performedOn($deliverable)
                ->causedBy($user)
                ->log("Cliente descarregou entregável «{$deliverable->title}»");

            return;
        }

        abort(403);
    }

    private function baseQuery(Project $project)
    {
        return ProjectDeliverable::where('project_id', $project->id)
            ->with(['uploader', 'approver'])
            ->latest();
    }

    private function assertTechnicianAssigned(Project $project, User $technician): void
    {
        $ok = ProjectAssignment::where('project_id', $project->id)
            ->where('assigned_to', $technician->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($ok, 403, 'Não está atribuído a este projecto.');
    }

    private function assertProjectAllowsUpload(Project $project): void
    {
        if ($project->contract_phase === ProjectContractPhase::Closed) {
            throw ValidationException::withMessages([
                'project' => ['O projecto está encerrado.'],
            ]);
        }
    }

    private function assertDeliverableOnProject(Project $project, ProjectDeliverable $deliverable): void
    {
        abort_unless($deliverable->project_id === $project->id, 404);
    }

    /** @param list<string> $allowed */
    private function assertStatus(ProjectDeliverable $deliverable, array $allowed): void
    {
        if (! in_array($deliverable->status, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ['O entregável não está disponível para esta acção.'],
            ]);
        }
    }
}
