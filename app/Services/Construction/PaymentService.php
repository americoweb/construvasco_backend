<?php

namespace App\Services\Construction;

use App\Enums\ProjectPaymentPhase;
use App\Enums\ProjectPaymentStatus;
use App\Enums\ProjectPaymentType;
use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentProofSubmittedMail;
use App\Mail\PaymentRejectedMail;
use App\Models\Construction\ProjectPayment;
use App\Models\Project;
use App\Models\User;
use App\Services\Mail\EmailDispatcher;
use App\Services\Notifications\NotificationService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private const PROOF_MIMES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
    ];

    private const PROOF_MAX_KB = 10240;

    public function __construct(
        private FileStorageService $storage,
        private EmailDispatcher $emails,
        private NotificationService $notifications,
    ) {}

    public function createArchitecturePayment(Project $project, User $customer, float $amount): ProjectPayment
    {
        $payment = ProjectPayment::create([
            'project_id' => $project->id,
            'user_id' => $customer->id,
            'type' => ProjectPaymentType::Architecture,
            'phase' => ProjectPaymentPhase::Architecture,
            'provider' => 'manual',
            'reference' => 'ARCH-' . $project->id . '-' . Str::upper(Str::random(8)),
            'amount' => $amount,
            'currency' => 'MZN',
            'status' => ProjectPaymentStatus::Pending,
        ]);

        activity('payments')
            ->performedOn($payment)
            ->causedBy($customer)
            ->withProperties(['amount' => $amount])
            ->log('Pagamento de arquitectura criado, valor ' . number_format($amount, 2, ',', '.') . ' MT, estado pendente');

        return $payment;
    }

    public function createConstructionPayment(Project $project, User $customer, float $amount): ProjectPayment
    {
        $payment = ProjectPayment::create([
            'project_id' => $project->id,
            'user_id' => $customer->id,
            'type' => ProjectPaymentType::Construction,
            'phase' => ProjectPaymentPhase::Construction,
            'provider' => 'manual',
            'reference' => 'CONST-' . $project->id . '-' . Str::upper(Str::random(8)),
            'amount' => $amount,
            'currency' => 'MZN',
            'status' => ProjectPaymentStatus::Pending,
        ]);

        activity('payments')
            ->performedOn($payment)
            ->causedBy($customer)
            ->withProperties(['amount' => $amount])
            ->log('Pagamento de obra criado, valor ' . number_format($amount, 2, ',', '.') . ' MT, estado pendente');

        return $payment;
    }

    public function architecturePaymentForProject(Project $project): ?ProjectPayment
    {
        return ProjectPayment::where('project_id', $project->id)
            ->where('phase', ProjectPaymentPhase::Architecture)
            ->latest('id')
            ->first();
    }

    public function constructionPaymentForProject(Project $project): ?ProjectPayment
    {
        return ProjectPayment::where('project_id', $project->id)
            ->where('phase', ProjectPaymentPhase::Construction)
            ->latest('id')
            ->first();
    }

    public function isArchitecturePaymentConfirmed(Project $project): bool
    {
        $payment = $this->architecturePaymentForProject($project);

        return $payment && $payment->status === ProjectPaymentStatus::Confirmed;
    }

    public function uploadProof(
        Project $project,
        ProjectPayment $payment,
        User $customer,
        UploadedFile $file,
        ?string $notes = null,
    ): ProjectPayment {
        abort_unless($project->client_user_id === $customer->id, 403);
        abort_unless($payment->project_id === $project->id, 404);

        $status = $payment->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }

        if (! in_array($status, [ProjectPaymentStatus::Pending->value, ProjectPaymentStatus::Rejected->value], true)) {
            throw ValidationException::withMessages([
                'payment' => ['Este pagamento não aceita novo comprovativo neste estado.'],
            ]);
        }

        $this->storage->validateFile($file, self::PROOF_MIMES, self::PROOF_MAX_KB);

        $stored = $this->storage->storePrivateFile($file, "projects/{$project->id}/proofs");

        $mergedNotes = $this->mergeNotes($payment->notes, $notes);

        $payment->update([
            'status' => ProjectPaymentStatus::ProofSubmitted,
            'proof_path' => $stored['path'],
            'proof_uploaded_at' => now(),
            'notes' => $mergedNotes,
            'rejected_reason' => null,
        ]);

        activity('payments')
            ->performedOn($payment)
            ->causedBy($customer)
            ->log('Cliente submeteu comprovativo de pagamento');

        $this->notifyManagersProofSubmitted($payment->fresh(['project', 'user']));

        return $payment->fresh(['confirmedByUser', 'user']);
    }

    public function confirm(Project $project, ProjectPayment $payment, User $manager): ProjectPayment
    {
        abort_unless($payment->project_id === $project->id, 404);
        $this->assertStatus($payment, [ProjectPaymentStatus::ProofSubmitted]);

        $payment->update([
            'status' => ProjectPaymentStatus::Confirmed,
            'confirmed_by' => $manager->id,
            'confirmed_at' => now(),
        ]);

        activity('payments')
            ->performedOn($payment)
            ->causedBy($manager)
            ->log('Gestor confirmou pagamento de ' . $this->phaseLabel($payment));

        $payment->load(['project', 'user']);
        if ($payment->user) {
            $this->emails->dispatchIdempotent(
                'payment_confirmed',
                $payment->user,
                new PaymentConfirmedMail($payment),
                ProjectPayment::class,
                $payment->id,
            );
        }

        return $payment->fresh(['confirmedByUser', 'user']);
    }

    public function reject(
        Project $project,
        ProjectPayment $payment,
        User $manager,
        string $reason,
    ): ProjectPayment {
        abort_unless($payment->project_id === $project->id, 404);
        $this->assertStatus($payment, [ProjectPaymentStatus::ProofSubmitted]);

        $payment->update([
            'status' => ProjectPaymentStatus::Rejected,
            'rejected_reason' => $reason,
        ]);

        activity('payments')
            ->performedOn($payment)
            ->causedBy($manager)
            ->withProperties(['rejection_reason' => $reason])
            ->log("Gestor rejeitou comprovativo de pagamento, motivo: {$reason}");

        $payment->load(['project', 'user']);
        if ($payment->user) {
            $this->emails->dispatchIdempotent(
                'payment_rejected',
                $payment->user,
                new PaymentRejectedMail($payment),
                ProjectPayment::class,
                $payment->id,
            );
        }

        return $payment->fresh(['confirmedByUser', 'user']);
    }

    /** @return Collection<int, ProjectPayment> */
    public function listPendingForManager(): Collection
    {
        return ProjectPayment::with(['project.client', 'user'])
            ->where('status', ProjectPaymentStatus::ProofSubmitted)
            ->latest('proof_uploaded_at')
            ->get();
    }

    public function pendingCount(): int
    {
        return ProjectPayment::where('status', ProjectPaymentStatus::ProofSubmitted)->count();
    }

    private function mergeNotes(?string $existing, ?string $incoming): ?string
    {
        $incoming = trim((string) $incoming);
        if ($incoming === '') {
            return $existing;
        }
        if (! $existing) {
            return $incoming;
        }

        return $existing . "\n---\n" . $incoming;
    }

    private function phaseLabel(ProjectPayment $payment): string
    {
        $phase = $payment->phase;
        if ($phase instanceof \BackedEnum) {
            $phase = $phase->value;
        }

        return $phase === ProjectPaymentPhase::Construction->value ? 'obra' : 'arquitectura';
    }

    private function assertStatus(ProjectPayment $payment, array $allowed): void
    {
        $status = $payment->status;
        if ($status instanceof \BackedEnum) {
            $status = $status->value;
        }
        $allowedValues = array_map(
            fn ($s) => $s instanceof \BackedEnum ? $s->value : $s,
            $allowed
        );
        if (! in_array($status, $allowedValues, true)) {
            throw ValidationException::withMessages([
                'payment' => ['O pagamento não está disponível para esta acção.'],
            ]);
        }
    }

    private function notifyManagersProofSubmitted(ProjectPayment $payment): void
    {
        $managers = User::role(['project_manager', 'admin'], 'api')
            ->where('is_active', true)
            ->get();

        foreach ($managers as $manager) {
            $proofAt = $payment->proof_uploaded_at?->timestamp ?? now()->timestamp;
            $idempotencyId = abs(crc32("{$payment->id}:{$manager->id}:{$proofAt}"));

            $this->emails->dispatchIdempotent(
                'payment_proof_submitted',
                $manager,
                new PaymentProofSubmittedMail($payment),
                ProjectPayment::class,
                $idempotencyId,
            );

            $this->notifications->notify($manager, 'payment_proof_submitted', [
                'title' => 'Comprovativo de pagamento',
                'message' => 'Novo comprovativo aguarda confirmação.',
                'reference_type' => ProjectPayment::class,
                'reference_id' => $payment->id,
            ]);
        }
    }
}
