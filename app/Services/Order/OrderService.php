<?php

namespace App\Services\Order;

use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use App\Models\Order\Order;
use App\Models\Order\OrderStatusHistory;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class OrderService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected OrderItemRepositoryInterface $orderItemRepository
    ) {}

    public function findById(int $id): Order
    {
        $order = $this->orderRepository->findById($id);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }
        
        return $order;
    }

    public function findByUuid(string $uuid): Order
    {
        $order = $this->orderRepository->findByUuid($uuid);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }
        
        return $order;
    }

    public function findByOrderNumber(string $orderNumber): Order
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }
        
        return $order;
    }

    public function create(array $orderData, array $items): Order
    {
        return DB::transaction(function () use ($orderData, $items) {
            // Set defaults
            if (!isset($orderData['status'])) {
                $orderData['status'] = OrderStatus::PENDING;
            }
            
            if (!isset($orderData['payment_status'])) {
                $orderData['payment_status'] = PaymentStatus::PENDING;
            }
            
            if (!isset($orderData['shipping_cost'])) {
                $orderData['shipping_cost'] = 0;
            }
            
            if (!isset($orderData['tax_amount'])) {
                $orderData['tax_amount'] = 0;
            }
            
            if (!isset($orderData['discount_amount'])) {
                $orderData['discount_amount'] = 0;
            }
            
            // Calculate subtotal from items
            $subtotal = 0;
            foreach ($items as $item) {
                $subtotal += $item['unit_price'] * $item['quantity'];
            }
            
            $orderData['subtotal'] = $subtotal;
            $orderData['total_amount'] = $subtotal + $orderData['shipping_cost'] + $orderData['tax_amount'] - $orderData['discount_amount'];
            
            // Log order data before creation to verify user_id is included
            \Illuminate\Support\Facades\Log::info('OrderService: Creating order with data', [
                'user_id' => $orderData['user_id'] ?? 'NOT SET',
                'order_number' => $orderData['order_number'] ?? 'NOT SET',
                'has_user_id' => isset($orderData['user_id']) && !empty($orderData['user_id'])
            ]);
            
            // Create order
            $order = $this->orderRepository->create($orderData);
            
            // Log after creation to verify user_id was saved
            \Illuminate\Support\Facades\Log::info('OrderService: Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $order->user_id ?? 'NULL',
                'user_id_in_db' => $order->getOriginal('user_id') ?? 'NULL'
            ]);
            
            // Create order items
            foreach ($items as $itemData) {
                $itemData['order_id'] = $order->id;
                $this->orderItemRepository->create($itemData);
            }
            
            // Log initial status
            $this->logStatusChange($order, null, $order->status, 'Pedido criado');
            
            return $order->fresh(['items']);
        });
    }

    public function update(int $id, array $data): Order
    {
        return $this->orderRepository->update($id, $data);
    }

    public function getOrderWithDetails(int $id): Order
    {
        $order = $this->orderRepository->getWithItems($id);
        
        if (!$order) {
            throw new \Exception('Pedido não encontrado');
        }
        
        return $order;
    }

    public function getByUser(int $userId): Collection
    {
        return $this->orderRepository->getByUser($userId);
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginateByUser($userId, $perPage);
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->orderRepository->getBySession($sessionId);
    }

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->paginate($filters, $perPage);
    }

    public function updateStatus(int $id, OrderStatus $newStatus, string $notes = null): Order
    {
        $order = $this->findById($id);
        $previousStatus = $order->status;
        
        if (!$previousStatus->canTransitionTo($newStatus)) {
            throw new \Exception("Não é possível mudar o status de {$previousStatus->label()} para {$newStatus->label()}");
        }
        
        $updateData = ['status' => $newStatus];
        
        // Set timestamp based on status
        switch ($newStatus) {
            case OrderStatus::CONFIRMED:
                $updateData['confirmed_at'] = now();
                break;
            case OrderStatus::SHIPPED:
                $updateData['shipped_at'] = now();
                break;
            case OrderStatus::DELIVERED:
                $updateData['delivered_at'] = now();
                break;
            case OrderStatus::CANCELLED:
                $updateData['cancelled_at'] = now();
                if ($notes) {
                    $updateData['cancellation_reason'] = $notes;
                }
                break;
        }
        
        $order = $this->orderRepository->update($id, $updateData);
        
        // Log status change
        $this->logStatusChange($order, $previousStatus, $newStatus, $notes);
        
        return $order;
    }

    public function confirmOrder(int $id): Order
    {
        $order = $this->updateStatus($id, OrderStatus::CONFIRMED, 'Pedido confirmado');
        $this->bridgeToJobCard($order);
        
        return $order->fresh(['items', 'statusHistory', 'jobCard']);
    }

    public function markInProduction(int $id): Order
    {
        return $this->updateStatus($id, OrderStatus::IN_PRODUCTION, 'Pedido em produção');
    }

    public function markShipped(int $id): Order
    {
        return $this->updateStatus($id, OrderStatus::SHIPPED, 'Pedido enviado');
    }

    public function markDelivered(int $id): Order
    {
        return $this->updateStatus($id, OrderStatus::DELIVERED, 'Pedido entregue');
    }

    public function cancelOrder(int $id, string $reason = null): Order
    {
        $order = $this->findById($id);
        
        if (!$order->canBeCancelled()) {
            throw new \Exception('Este pedido não pode ser cancelado');
        }
        
        return $this->updateStatus($id, OrderStatus::CANCELLED, $reason ?? 'Pedido cancelado pelo cliente');
    }

    public function updatePaymentStatus(int $id, PaymentStatus $paymentStatus): Order
    {
        $updateData = [
            'payment_status' => $paymentStatus
        ];
        
        // Set paid_at timestamp when marking as paid
        if ($paymentStatus === PaymentStatus::PAID) {
            $order = $this->findById($id);
            // Only set paid_at if not already set
            if (!$order->paid_at) {
                $updateData['paid_at'] = now();
            }
        }
        
        return $this->orderRepository->update($id, $updateData);
    }

    public function markAsPaid(int $id): Order
    {
        return $this->updatePaymentStatus($id, PaymentStatus::PAID);
    }

    public function getPendingOrders(): Collection
    {
        return $this->orderRepository->getPending();
    }

    public function getActiveOrders(): Collection
    {
        return $this->orderRepository->getActive();
    }

    public function addItem(int $orderId, array $itemData): Order
    {
        $order = $this->findById($orderId);
        
        if (!$order->isPending()) {
            throw new \Exception('Não é possível adicionar itens a um pedido que não está pendente');
        }
        
        $itemData['order_id'] = $orderId;
        $this->orderItemRepository->create($itemData);
        
        $order->refresh();
        $order->calculateTotals();
        $order->save();
        
        return $order->fresh(['items']);
    }

    public function removeItem(int $orderId, int $itemId): Order
    {
        $order = $this->findById($orderId);
        
        if (!$order->isPending()) {
            throw new \Exception('Não é possível remover itens de um pedido que não está pendente');
        }
        
        $this->orderItemRepository->delete($itemId);
        
        $order->refresh();
        $order->calculateTotals();
        $order->save();
        
        return $order->fresh(['items']);
    }

    public function updateShippingAddress(int $id, array $addressData): Order
    {
        $order = $this->findById($id);
        
        if ($order->isDelivered() || $order->isCancelled()) {
            throw new \Exception('Não é possível atualizar o endereço deste pedido');
        }
        
        return $this->orderRepository->update($id, $addressData);
    }

    protected function logStatusChange(Order $order, ?OrderStatus $previousStatus, OrderStatus $newStatus, ?string $notes = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'notes' => $notes,
            'metadata' => [
                'timestamp' => now()->toIso8601String(),
                'ip_address' => request()->ip(),
            ],
        ]);
    }

    protected function bridgeToJobCard(Order $order): void
    {
        // Prevent duplicate job cards for same order
        if ($order->jobCard()->exists()) {
            return;
        }

        $clientId = $order->user_id;

        // Guest fallback logic
        if (!$clientId) {
            $identifier = $order->billing_email ?? $order->shipping_whatsapp ?? 'guest_' . uniqid() . '@amazing.co.mz';
            $guestUser = \App\Models\User::firstOrCreate(
                ['identifier' => $identifier],
                [
                    'name' => $order->billing_name ?? $order->shipping_name ?? 'Guest Client',
                    'password' => bcrypt(\Illuminate\Support\Str::random(16)),
                    'type' => 'client',
                    'is_active' => true,
                ]
            );
            $clientId = $guestUser->id;

            $this->orderRepository->update($order->id, ['user_id' => $clientId]);
            $order->user_id = $clientId;
        }

        $items = [];
        $order->loadMissing('items');
        foreach ($order->items as $item) {
            $notes = [];
            if ($item->print_area_name) $notes[] = "Área: {$item->print_area_name}";
            if ($item->design_prompt) $notes[] = "Prompt: {$item->design_prompt}";
            if ($item->notes) $notes[] = "Notas: {$item->notes}";

            $items[] = [
                'product_id' => $item->product_id,
                'product_color_id' => $item->product_color_id,
                'product_type' => $item->product_name,
                'quantity' => $item->quantity,
                'material' => $item->color_name,
                'notes' => implode(' | ', $notes),
            ];
        }

        $jobCardService = app(\App\Services\JobCard\JobCardService::class);
        $jobCardService->create([
            'client_id' => $clientId,
            'created_by' => $clientId, 
            'title' => "Pedido Online #{$order->order_number}",
            'description' => $order->notes ?? 'Gerado automaticamente a partir do pedido online.',
            'status' => \App\Enums\JobCard\JobCardStatus::BRIEFING,
            'deadline' => now()->addDays(7),
            'order_id' => $order->id,
            'priority' => \App\Enums\JobCard\JobCardPriority::MEDIUM,
        ], $items);
    }
}
