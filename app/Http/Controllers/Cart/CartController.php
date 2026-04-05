<?php

namespace App\Http\Controllers\Cart;

use App\Http\Controllers\Controller;
use App\Services\Cart\CartService;
use App\Http\Requests\Cart\AddToCartRequest;
use App\Http\Requests\Cart\UpdateCartItemRequest;
use App\Http\Resources\Cart\CartResource;
use App\Http\Resources\Cart\CartItemResource;
use App\Http\Resources\Cart\CartSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    public function show(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->get('session_id');

        $cart = $this->cartService->getOrCreateCart($userId, $sessionId);
        $cart->load('items.product', 'items.color', 'items.printArea', 'items.design');

        return response()->json([
            'data' => new CartResource($cart)
        ]);
    }

    public function showById(int $id): JsonResponse
    {
        $cart = $this->cartService->getCartWithItems($id);

        return response()->json([
            'data' => new CartResource($cart)
        ]);
    }

    public function showBySession(string $sessionId): JsonResponse
    {
        $cart = $this->cartService->getCartBySession($sessionId);

        if (!$cart) {
            return response()->json([
                'data' => null,
                'message' => 'Carrinho não encontrado'
            ]);
        }

        return response()->json([
            'data' => new CartResource($cart)
        ]);
    }

    public function addItem(AddToCartRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->get('session_id');

        $cart = $this->cartService->getOrCreateCart($userId, $sessionId);
        $item = $this->cartService->addItem($cart->id, $request->validated());

        return response()->json([
            'data' => new CartItemResource($item),
            'message' => 'Item adicionado ao carrinho'
        ], 201);
    }

    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $item = $this->cartService->updateItemQuantity(
            $itemId,
            $request->quantity
        );

        return response()->json([
            'data' => new CartItemResource($item),
            'message' => 'Quantidade atualizada'
        ]);
    }

    public function removeItem(int $itemId): JsonResponse
    {
        $this->cartService->removeItem($itemId);

        return response()->json([
            'message' => 'Item removido do carrinho'
        ]);
    }

    public function clear(int $cartId): JsonResponse
    {
        $this->cartService->clearCart($cartId);

        return response()->json([
            'message' => 'Carrinho esvaziado'
        ]);
    }

    public function summary(int $cartId): JsonResponse
    {
        $summary = $this->cartService->getCartSummary($cartId);

        return response()->json([
            'data' => new CartSummaryResource($summary)
        ]);
    }

    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $userId = $request->user()->id;
        $cart = $this->cartService->mergeGuestCart($request->session_id, $userId);

        return response()->json([
            'data' => new CartResource($cart),
            'message' => 'Carrinhos mesclados com sucesso'
        ]);
    }

    public function convertToOrder(int $cartId): JsonResponse
    {
        $orderItems = $this->cartService->convertToOrderItems($cartId);

        return response()->json([
            'data' => $orderItems,
            'message' => 'Itens prontos para checkout'
        ]);
    }

    public function extendExpiration(int $cartId): JsonResponse
    {
        $cart = $this->cartService->extendExpiration($cartId);

        return response()->json([
            'data' => new CartResource($cart),
            'message' => 'Validade do carrinho estendida'
        ]);
    }
}
