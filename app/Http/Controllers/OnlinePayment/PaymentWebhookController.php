<?php

namespace App\Http\Controllers\OnlinePayment;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Handle payment webhook from e2payments gateway
     * 
     * This endpoint receives callbacks when payment status changes
     * Expected payload structure (may vary based on gateway):
     * {
     *   "reference": "ORDER_123",
     *   "transaction_id": "TXN_ABC123",
     *   "status": "success|failed|pending",
     *   "amount": 1000.00,
     *   "phone": "+258841234567",
     *   "timestamp": "2025-11-19T12:00:00Z"
     * }
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Log incoming webhook for debugging
            Log::info('Payment webhook received', [
                'headers' => $request->headers->all(),
                'body' => $request->all(),
                'ip' => $request->ip()
            ]);

            // Validate webhook signature if provided by gateway
            // TODO: Add signature validation if gateway provides it
            // if (!$this->validateWebhookSignature($request)) {
            //     return response()->json(['error' => 'Invalid signature'], 401);
            // }

            $webhookData = $request->all();

            // Hard-cut phase: legacy order webhook processing is disabled.
            // The new project payment workflow is handled by the construction domain.
            $this->paymentService->handlePaymentWebhook($webhookData);

            return response()->json([
                'success' => true,
                'message' => 'Webhook accepted'
            ], 202);

        } catch (\Exception $e) {
            Log::error('Payment webhook exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate webhook signature (if gateway provides it)
     * TODO: Implement based on gateway documentation
     */
    protected function validateWebhookSignature(Request $request): bool
    {
        // Example implementation:
        // $signature = $request->header('X-Webhook-Signature');
        // $payload = $request->getContent();
        // $expectedSignature = hash_hmac('sha256', $payload, config('payment.webhook_secret'));
        // return hash_equals($expectedSignature, $signature);
        
        return true; // For now, accept all webhooks (implement proper validation in production)
    }
}

