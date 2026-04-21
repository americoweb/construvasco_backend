<?php

namespace App\Services\JobCard;

use App\Repositories\JobCard\Contracts\JobCardRepositoryInterface;
use App\Models\JobCard\JobCard;
use App\Models\JobCard\JobCardItem;
use App\Models\JobCard\JobCardFile;
use App\Models\JobCard\JobCardFeedback;
use App\Enums\JobCard\JobCardStatus;
use App\Enums\JobCard\JobCardPriority;
use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class JobCardService
{
    /** Maximum simultaneous priority_override active jobs (governance rule) */
    const MAX_ACTIVE_OVERRIDES = 3;

    public function __construct(
        protected JobCardRepositoryInterface $repository
    ) {}

    // -----------------------------------------------------------------------
    // Finders
    // -----------------------------------------------------------------------

    public function findById(int $id): JobCard
    {
        $jobCard = $this->repository->findById($id);
        if (!$jobCard) {
            throw new \Exception('Job Card não encontrado');
        }
        return $jobCard;
    }

    public function findByUuid(string $uuid): JobCard
    {
        $jobCard = $this->repository->findByUuid($uuid);
        if (!$jobCard) {
            throw new \Exception('Job Card não encontrado');
        }
        return $jobCard;
    }

    public function getWithRelations(int $id): JobCard
    {
        $jobCard = $this->repository->getWithRelations($id);
        if (!$jobCard) {
            throw new \Exception('Job Card não encontrado');
        }
        return $jobCard;
    }

    // -----------------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------------

    public function create(array $data, array $items = []): JobCard
    {
        return DB::transaction(function () use ($data, $items) {
            $jobCard = $this->repository->create($data);

            foreach ($items as $item) {
                $item['job_card_id'] = $jobCard->id;
                JobCardItem::create($item);
            }

            return $jobCard->fresh(['items']);
        });
    }

    public function update(int $id, array $data): JobCard
    {
        return $this->repository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $jobCard = $this->findById($id);
        if (!$jobCard->canBeCancelled()) {
            throw new \Exception('Não é possível remover um Job Card concluído');
        }
        return $this->repository->delete($id);
    }

    // -----------------------------------------------------------------------
    // Listing
    // -----------------------------------------------------------------------

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function getKanbanBoard(): array
    {
        return $this->repository->getKanbanBoard();
    }

    public function getActive(): Collection
    {
        return $this->repository->getActive();
    }

    // -----------------------------------------------------------------------
    // Status management
    // -----------------------------------------------------------------------

    public function updateStatus(int $id, JobCardStatus $newStatus, ?string $notes = null, ?int $changedBy = null): JobCard
    {
        $jobCard         = $this->findById($id);
        $previousStatus  = $jobCard->status;

        if (!$previousStatus->canTransitionTo($newStatus)) {
            throw new \Exception(
                "Não é possível mudar o status de \"{$previousStatus->label()}\" para \"{$newStatus->label()}\""
            );
        }

        // On revision: increment revision count + validate limit
        if ($newStatus === JobCardStatus::REVISION) {
            if ($jobCard->isOverRevisionLimit()) {
                throw new \Exception(
                    "Limite de revisões atingido ({$jobCard->revision_limit}). Contacte o gestor para aprovar revisão extra."
                );
            }
            $this->repository->update($id, [
                'status'         => $newStatus,
                'revision_count' => $jobCard->revision_count + 1,
            ]);
        } else {
            $this->repository->update($id, ['status' => $newStatus]);
        }

        // Log feedback/note if notes supplied
        if ($notes && $changedBy) {
            JobCardFeedback::create([
                'job_card_id' => $id,
                'created_by'  => $changedBy,
                'comment'     => $notes,
                'role'        => 'comercial',
                'version'     => $jobCard->revision_count,
                'is_approved' => $newStatus === JobCardStatus::APPROVAL,
            ]);
        }

        $updated = $this->findById($id);

        // Dispatch WhatsApp notification to client
        $this->notifyClientStatusChange($updated, $newStatus);

        return $updated;
    }

    public function cancel(int $id, ?string $reason = null, ?int $changedBy = null): JobCard
    {
        $jobCard = $this->findById($id);

        if (!$jobCard->canBeCancelled()) {
            throw new \Exception('Este Job Card não pode ser cancelado');
        }

        $this->repository->update($id, ['status' => JobCardStatus::CANCELLED]);

        if ($reason && $changedBy) {
            JobCardFeedback::create([
                'job_card_id' => $id,
                'created_by'  => $changedBy,
                'comment'     => $reason,
                'role'        => 'comercial',
                'version'     => $jobCard->revision_count,
                'is_approved' => false,
            ]);
        }

        $cancelled = $this->findById($id);

        // Notify client of cancellation
        $this->notifyClientStatusChange($cancelled, JobCardStatus::CANCELLED);

        return $cancelled;
    }

    // -----------------------------------------------------------------------
    // Priority management (with governance rules)
    // -----------------------------------------------------------------------

    /**
     * Escalate priority.
     * Governance:
     *  - Only HIGH can be set (medium/low are free)
     *  - priority_override requires a reason
     *  - Max MAX_ACTIVE_OVERRIDES simultaneous overrides
     */
    public function updatePriority(
        int $id,
        JobCardPriority $priority,
        bool $override = false,
        ?string $reason = null
    ): JobCard {
        if ($override && !$reason) {
            throw new \Exception('É obrigatório indicar o motivo para activar a prioridade urgente');
        }

        if ($override) {
            $currentOverrides = $this->repository->countActiveOverrides();
            if ($currentOverrides >= self::MAX_ACTIVE_OVERRIDES) {
                throw new \Exception(
                    "Limite de jobs urgentes atingido (máx. " . self::MAX_ACTIVE_OVERRIDES . "). Remova outro urgente primeiro."
                );
            }
        }

        return $this->repository->update($id, [
            'priority'          => $priority,
            'priority_override' => $override,
            'priority_reason'   => $override ? $reason : null,
        ]);
    }

    public function removeOverride(int $id): JobCard
    {
        return $this->repository->update($id, [
            'priority_override' => false,
            'priority_reason'   => null,
        ]);
    }

    // -----------------------------------------------------------------------
    // Designer assignment
    // -----------------------------------------------------------------------

    public function assignDesigner(int $id, int $designerId): JobCard
    {
        $jobCard = $this->findById($id);

        if ($jobCard->isDone() || $jobCard->isCancelled()) {
            throw new \Exception('Não é possível reatribuir um Job Card concluído ou cancelado');
        }

        return $this->repository->update($id, ['assigned_designer_id' => $designerId]);
    }

    // -----------------------------------------------------------------------
    // Item management
    // -----------------------------------------------------------------------

    public function addItem(int $id, array $itemData): JobCard
    {
        $jobCard = $this->findById($id);

        if (!in_array($jobCard->status, [JobCardStatus::DRAFT, JobCardStatus::BRIEFING])) {
            throw new \Exception('Itens só podem ser adicionados nas fases de Rascunho ou Briefing');
        }

        JobCardItem::create(['job_card_id' => $id] + $itemData);

        return $jobCard->fresh(['items']);
    }

    public function removeItem(int $id, int $itemId): JobCard
    {
        $jobCard = $this->findById($id);

        if (!in_array($jobCard->status, [JobCardStatus::DRAFT, JobCardStatus::BRIEFING])) {
            throw new \Exception('Itens só podem ser removidos nas fases de Rascunho ou Briefing');
        }

        JobCardItem::where('job_card_id', $id)->where('id', $itemId)->delete();

        return $jobCard->fresh(['items']);
    }

    // -----------------------------------------------------------------------
    // Feedback
    // -----------------------------------------------------------------------

    public function addFeedback(int $id, array $data): JobCardFeedback
    {
        $jobCard = $this->findById($id);

        return JobCardFeedback::create([
            'job_card_id' => $id,
            'created_by'  => $data['created_by'],
            'comment'     => $data['comment'],
            'role'        => $data['role'],
            'version'     => $jobCard->revision_count,
            'is_approved' => $data['is_approved'] ?? false,
        ]);
    }

    // -----------------------------------------------------------------------
    // Order link
    // -----------------------------------------------------------------------

    public function linkOrder(int $id, int $orderId): JobCard
    {
        return $this->repository->update($id, ['order_id' => $orderId]);
    }

    // -----------------------------------------------------------------------
    // WhatsApp notifications
    // -----------------------------------------------------------------------

    /**
     * Statuses that trigger a customer notification (informational only).
     * DRAFT and BRIEFING are internal — client doesn't need a message for those.
     */
    private const NOTIFY_STATUSES = [
        JobCardStatus::DESIGN,
        JobCardStatus::REVISION,
        JobCardStatus::APPROVAL,
        JobCardStatus::PRODUCTION,
        JobCardStatus::DONE,
        JobCardStatus::CANCELLED,
    ];

    private function notifyClientStatusChange(JobCard $jobCard, JobCardStatus $status): void
    {
        if (!in_array($status, self::NOTIFY_STATUSES)) {
            return;
        }

        // Load client relationship if not already loaded
        $jobCard->loadMissing('client');
        $client = $jobCard->client;

        if (!$client || empty($client->identifier)) {
            return;
        }

        $message = $this->buildStatusMessage($jobCard, $status);

        Log::info('[WhatsApp] Dispatching notification', [
            'job_card'  => $jobCard->job_number,
            'status'    => $status->value,
            'client'    => $client->name,
            'phone'     => $client->identifier,
            'message'   => $message,
        ]);

        // dispatchSync runs immediately (no queue worker needed) — good for testing
        SendWhatsAppNotification::dispatchSync($client->identifier, $message);
    }

    private function buildStatusMessage(JobCard $jobCard, JobCardStatus $status): string
    {
        $name   = $jobCard->client?->name ?? 'Cliente';
        $number = $jobCard->job_number;
        $title  = $jobCard->title;

        return match ($status) {
            JobCardStatus::DESIGN =>
                "Olá {$name}! 👋\n\nO seu trabalho *{$number} – {$title}* entrou em fase de Design. O nosso designer já está a trabalhar no seu projecto.\n\nEntraremos em contacto assim que tivermos algo para mostrar.",

            JobCardStatus::REVISION =>
                "Olá {$name}! 📋\n\nTemos uma revisão do seu trabalho *{$number} – {$title}* pronta para a sua análise.\n\nAguardamos o seu feedback.",

            JobCardStatus::APPROVAL =>
                "Olá {$name}! ✅\n\nO design do seu trabalho *{$number} – {$title}* está pronto e aguarda a sua aprovação final.\n\nPor favor reveja e confirme para prosseguirmos para produção.",

            JobCardStatus::PRODUCTION =>
                "Olá {$name}! 🏭\n\nÓptimas notícias! O seu trabalho *{$number} – {$title}* foi aprovado e entrou em produção.\n\nAvisaremos quando estiver pronto.",

            JobCardStatus::DONE =>
                "Olá {$name}! 🎉\n\nO seu trabalho *{$number} – {$title}* está concluído e pronto para entrega!\n\nObrigado por confiar em nós.",

            JobCardStatus::CANCELLED =>
                "Olá {$name}.\n\nInformamos que o seu trabalho *{$number} – {$title}* foi cancelado.\n\nSe tiver questões ou pretender reagendar, não hesite em contactar-nos.",

            default => '',
        };
    }
}
