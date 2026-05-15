<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderService;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Http\Resources\Order\OrderResource;
use App\Http\Resources\Order\OrderListResource;
use App\Enums\Order\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => OrderListResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    public function store(CreateOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $items = $validated['items'];
        unset($validated['items']);

        $order = $this->orderService->create($validated, $items);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido criado com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderWithDetails($id);

        return response()->json([
            'data' => new OrderResource($order)
        ]);
    }

    public function showByOrderNumber(string $orderNumber): JsonResponse
    {
        $order = $this->orderService->findByOrderNumber($orderNumber);
        $order->load(['items', 'statusHistory', 'jobCard']);

        return response()->json([
            'data' => new OrderResource($order)
        ]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $order = $this->orderService->findByUuid($uuid);
        $order->load(['items', 'statusHistory', 'jobCard']);

        return response()->json([
            'data' => new OrderResource($order)
        ]);
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->update($id, $request->validated());

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido atualizado com sucesso'
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $status = OrderStatus::from($request->status);
        $order = $this->orderService->updateStatus($id, $status, $request->notes);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Status atualizado com sucesso'
        ]);
    }

    public function confirm(int $id): JsonResponse
    {
        $order = $this->orderService->confirmOrder($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido confirmado com sucesso'
        ]);
    }

    public function triage(int $id): JsonResponse
    {
        $order = $this->orderService->markTriaged($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido triado com sucesso'
        ]);
    }

    public function assign(int $id): JsonResponse
    {
        $order = $this->orderService->markAssigned($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido atribuído com sucesso'
        ]);
    }

    public function markInProduction(int $id): JsonResponse
    {
        $order = $this->orderService->markInProduction($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como em produção'
        ]);
    }

    public function markInDesign(int $id): JsonResponse
    {
        $order = $this->orderService->markInDesign($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como em projeto'
        ]);
    }

    public function markAwaitingClient(int $id): JsonResponse
    {
        $order = $this->orderService->markAwaitingClient($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como aguardando cliente'
        ]);
    }

    public function approve(int $id): JsonResponse
    {
        $order = $this->orderService->markApproved($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido aprovado com sucesso'
        ]);
    }

    public function markInExecution(int $id): JsonResponse
    {
        $order = $this->orderService->markInExecution($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como em execução'
        ]);
    }

    public function markShipped(int $id): JsonResponse
    {
        $order = $this->orderService->markShipped($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como enviado'
        ]);
    }

    public function markDelivered(int $id): JsonResponse
    {
        $order = $this->orderService->markDelivered($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como entregue'
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $reason = $request->get('reason');
        $order = $this->orderService->cancelOrder($id, $reason);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido cancelado com sucesso'
        ]);
    }

    public function markAsPaid(int $id): JsonResponse
    {
        $order = $this->orderService->markAsPaid($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pagamento registrado com sucesso'
        ]);
    }

    public function getBySession(string $sessionId): JsonResponse
    {
        $orders = $this->orderService->getBySession($sessionId);

        return response()->json([
            'data' => OrderListResource::collection($orders)
        ]);
    }

    public function getByUser(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $orders = $this->orderService->paginateByUser(
            $userId,
            (int) $request->get('per_page', 15),
            $request->filled('status') ? (string) $request->get('status') : null,
            $request->filled('search') ? (string) $request->get('search') : null
        );

        // Load items for each order
        $orders->getCollection()->load('items');

        return response()->json([
            'data' => OrderListResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ]
        ]);
    }

    public function getPending(): JsonResponse
    {
        $orders = $this->orderService->getPendingOrders();

        return response()->json([
            'data' => OrderListResource::collection($orders)
        ]);
    }

    public function getActive(): JsonResponse
    {
        $orders = $this->orderService->getActiveOrders();

        return response()->json([
            'data' => OrderListResource::collection($orders)
        ]);
    }

    public function updateShippingAddress(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->updateShippingAddress($id, $request->validated());

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Endereço atualizado com sucesso'
        ]);
    }
}
