<?php

namespace App\Services\Payment;

use App\Models\Order\Order;
use App\Services\Order\OrderService;
use App\Enums\Order\PaymentStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * Find order by payment reference
     */
    public function findOrderByReference(string $reference): ?Order
    {
        return Order::where('payment_reference', $reference)
            ->orWhere('order_number', $reference)
            ->first();
    }

    /**
     * Update order payment status from webhook
     */
    public function handlePaymentWebhook(array $webhookData): bool
    {
        try {
            $reference = $webhookData['reference'] ?? $webhookData['order_reference'] ?? null;
            $transactionId = $webhookData['transaction_id'] ?? $webhookData['transactionId'] ?? null;
            $status = $webhookData['status'] ?? $webhookData['payment_status'] ?? null;
            $amount = $webhookData['amount'] ?? null;

            if (!$reference) {
                Log::warning('Payment webhook received without reference', ['data' => $webhookData]);
                return false;
            }

            $order = $this->findOrderByReference($reference);

            if (!$order) {
                Log::warning('Payment webhook: Order not found', [
                    'reference' => $reference,
                    'data' => $webhookData
                ]);
                return false;
            }

            return DB::transaction(function () use ($order, $status, $transactionId, $amount, $webhookData) {
                // Map gateway status to our PaymentStatus enum
                $paymentStatus = $this->mapPaymentStatus($status);

                // Update payment information
                $updateData = [
                    'payment_status' => $paymentStatus,
                ];

                if ($transactionId) {
                    $updateData['payment_transaction_id'] = $transactionId;
                }

                if ($paymentStatus === PaymentStatus::PAID) {
                    $updateData['paid_at'] = now();
                    
                    // If order is still pending, confirm it
                    if ($order->status->value === 'pending') {
                        try {
                            $this->orderService->confirmOrder($order->id);
                        } catch (\Exception $e) {
                            Log::warning('Could not auto-confirm order after payment', [
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }

                $this->orderService->update($order->id, $updateData);

                Log::info('Payment webhook processed successfully', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'reference' => $reference,
                    'status' => $paymentStatus->value,
                    'transaction_id' => $transactionId
                ]);

                return true;
            });

        } catch (\Exception $e) {
            Log::error('Error processing payment webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $webhookData
            ]);
            return false;
        }
    }

    /**
     * Map payment gateway status to our PaymentStatus enum
     */
    protected function mapPaymentStatus(?string $gatewayStatus): PaymentStatus
    {
        if (!$gatewayStatus) {
            return PaymentStatus::PENDING;
        }

        $status = strtolower($gatewayStatus);

        return match($status) {
            'success', 'completed', 'paid', 'successful' => PaymentStatus::PAID,
            'failed', 'error', 'declined', 'cancelled' => PaymentStatus::FAILED,
            'refunded', 'reversed' => PaymentStatus::REFUNDED,
            default => PaymentStatus::PENDING,
        };
    }

    /**
     * Link payment to order
     */
    public function linkPaymentToOrder(Order $order, string $paymentMethod, ?string $transactionId = null, ?string $reference = null): Order
    {
        $updateData = [
            'payment_method' => $paymentMethod,
        ];

        if ($transactionId) {
            $updateData['payment_transaction_id'] = $transactionId;
        }

        if ($reference) {
            $updateData['payment_reference'] = $reference;
        } else {
            // Use order number as default reference
            $updateData['payment_reference'] = $order->order_number;
        }

        return $this->orderService->update($order->id, $updateData);
    }
}

