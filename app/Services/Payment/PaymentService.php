<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class PaymentService
{
    public function handlePaymentWebhook(array $webhookData): bool
    {
        Log::info('Payment webhook received during legacy hard-cut transition', [
            'payload' => $webhookData,
        ]);
        return true;
    }
}

