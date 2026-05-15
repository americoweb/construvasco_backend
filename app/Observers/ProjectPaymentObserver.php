<?php

namespace App\Observers;

use App\Constants\NotificationTypes;
use App\Enums\ProjectPaymentType;
use App\Models\Construction\ProjectPayment;
use App\Services\Credits\CreditService;
use App\Services\Notifications\NotificationService;

class ProjectPaymentObserver
{
    public function __construct(
        private CreditService $credits,
        private NotificationService $notifications
    ) {}

    public function updated(ProjectPayment $payment): void
    {
        if (!$payment->wasChanged('status')) {
            return;
        }

        if (!in_array($payment->status, ['paid', 'completed'], true)) {
            return;
        }

        $metadata = $payment->metadata ?? [];
        if ($metadata['side_effects_applied'] ?? false) {
            return;
        }

        if ($payment->type === ProjectPaymentType::CreditsPurchase && $payment->creditPackage && $payment->user) {
            $this->credits->purchase(
                $payment->user,
                $payment->creditPackage,
                $payment->reference
            );

            $this->notifications->notify($payment->user, NotificationTypes::CREDITS_PURCHASED, [
                'title' => 'Créditos adicionados',
                'message' => 'Os seus créditos foram creditados com sucesso.',
            ]);

            $payment->updateQuietly(['metadata' => array_merge($metadata, ['side_effects_applied' => true])]);

            return;
        }

        if ($payment->type === ProjectPaymentType::ProjectFinal && $payment->project) {
            $project = $payment->project;
            $project->update([
                'client_can_download' => true,
                'final_payment_status' => 'paid',
            ]);

            if ($project->client) {
                $this->notifications->notify($project->client, NotificationTypes::DELIVERABLES_AVAILABLE, [
                    'title' => 'Entregáveis disponíveis',
                    'message' => 'Pagamento confirmado. Pode descarregar os ficheiros do projecto.',
                    'reference_type' => get_class($project),
                    'reference_id' => $project->id,
                ], ['in_app', 'whatsapp']);
            }

            $payment->updateQuietly(['metadata' => array_merge($metadata, ['side_effects_applied' => true])]);
        }
    }
}
