<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Construction\ProjectPayment;
use App\Services\Payments\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('payment.webhook_secret');
        if ($secret) {
            $signature = $request->header('X-Webhook-Signature', $request->header('X-Signature'));
            $expected = hash_hmac('sha256', $request->getContent(), $secret);
            if (!hash_equals($expected, (string) $signature)) {
                Log::channel('payments')->warning('payment.webhook.invalid_signature');

                return response()->json(['message' => 'Invalid signature'], 403);
            }
        }

        $validated = $request->validate([
            'reference' => 'required|string',
            'status' => 'required|string',
            'transaction_id' => 'nullable|string',
        ]);

        Log::channel('payments')->info('payment.webhook.received', $validated);

        $payment = ProjectPayment::query()
            ->where('reference', $validated['reference'])
            ->orWhere('provider_reference', $validated['reference'])
            ->first();

        if (!$payment) {
            Log::channel('payments')->warning('payment.webhook.not_found', ['reference' => $validated['reference']]);

            return response()->json(['message' => 'Payment reference not found'], 404);
        }

        $incomingStatus = strtolower($validated['status']);
        $metadata = $payment->metadata ?? [];

        if (in_array($payment->status, ['paid', 'completed'], true) && ($metadata['webhook_processed'] ?? false)) {
            return response()->json(['success' => true, 'message' => 'Already processed']);
        }

        if (!PaymentGatewayService::isSuccessfulStatus($incomingStatus)) {
            $payment->update([
                'status' => $incomingStatus,
                'metadata' => array_merge($metadata, ['webhook' => $request->all()]),
            ]);

            return response()->json(['success' => true, 'message' => 'Status recorded']);
        }

        $payment->update([
            'status' => 'paid',
            'transaction_id' => $validated['transaction_id'] ?? $payment->transaction_id,
            'provider_reference' => $validated['reference'],
            'paid_at' => now(),
            'metadata' => array_merge($metadata, [
                'webhook' => $request->all(),
                'webhook_processed' => true,
                'webhook_processed_at' => now()->toIso8601String(),
            ]),
        ]);

        $payment->load(['project.client', 'creditPackage', 'user']);

        Log::channel('payments')->info('payment.webhook.processed', [
            'payment_id' => $payment->id,
            'reference' => $payment->reference,
        ]);

        return response()->json(['success' => true]);
    }
}
