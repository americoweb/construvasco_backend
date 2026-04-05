<?php

namespace App\Services\Cart;

use App\Repositories\Cart\Contracts\CartRepositoryInterface;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use Illuminate\Support\Facades\DB;

class CartService
{
    public function __construct(
        protected CartRepositoryInterface $cartRepository,
        protected CartItemRepositoryInterface $cartItemRepository
    ) {}

    public function getOrCreateCart(?int $userId = null, ?string $sessionId = null): Cart
    {
        if ($userId) {
            $cart = $this->cartRepository->getActiveByUser($userId);
        } elseif ($sessionId) {
            $cart = $this->cartRepository->getActiveBySession($sessionId);
        } else {
            throw new \Exception('User ID ou Session ID é obrigatório');
        }

        if (!$cart) {
            $cart = $this->cartRepository->create([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'expires_at' => now()->addDays(7),
            ]);
        }

        return $cart;
    }

    public function findById(int $id): Cart
    {
        $cart = $this->cartRepository->findById($id);
        
        if (!$cart) {
            throw new \Exception('Carrinho não encontrado');
        }
        
        return $cart;
    }

    public function findByUuid(string $uuid): Cart
    {
        $cart = $this->cartRepository->findByUuid($uuid);
        
        if (!$cart) {
            throw new \Exception('Carrinho não encontrado');
        }
        
        return $cart;
    }

    public function getCartWithItems(int $id): Cart
    {
        $cart = $this->cartRepository->getWithItems($id);
        
        if (!$cart) {
            throw new \Exception('Carrinho não encontrado');
        }
        
        return $cart;
    }

    public function getCartByUser(int $userId): ?Cart
    {
        return $this->cartRepository->getActiveByUser($userId);
    }

    public function getCartBySession(string $sessionId): ?Cart
    {
        return $this->cartRepository->getActiveBySession($sessionId);
    }

    public function getCartByUuid(string $uuid): ?Cart
    {
        return $this->cartRepository->findByUuid($uuid);
    }

    public function addItem(int $cartId, array $itemData): CartItem
    {
        $cart = $this->findById($cartId);
        
        if ($cart->isExpired()) {
            throw new \Exception('O carrinho expirou');
        }

        // Check if item already exists in cart
        $existingItem = $this->cartItemRepository->findInCart(
            $cartId,
            $itemData['product_id'],
            $itemData['product_color_id'],
            $itemData['product_print_area_id']
        );

        if ($existingItem) {
            // Update quantity if item exists
            $newQuantity = $existingItem->quantity + $itemData['quantity'];
            return $this->updateItemQuantity($existingItem->id, $newQuantity);
        }

        $itemData['cart_id'] = $cartId;
        $item = $this->cartItemRepository->create($itemData);
        
        // Refresh cart expiration
        $this->cartRepository->update($cartId, [
            'expires_at' => now()->addDays(7)
        ]);

        return $item->load(['product', 'color', 'printArea', 'design']);
    }

    public function updateItemQuantity(int $itemId, int $quantity): CartItem
    {
        if ($quantity < 1) {
            throw new \Exception('A quantidade deve ser pelo menos 1');
        }

        $item = $this->cartItemRepository->findById($itemId);
        
        if (!$item) {
            throw new \Exception('Item não encontrado');
        }

        // Check minimum quantity
        if ($item->product && $quantity < $item->product->min_quantity) {
            throw new \Exception("A quantidade mínima para este produto é {$item->product->min_quantity}");
        }

        $item->updateQuantity($quantity);

        return $item->load(['product', 'color', 'printArea', 'design']);
    }

    public function removeItem(int $itemId): bool
    {
        return $this->cartItemRepository->delete($itemId);
    }

    public function clearCart(int $cartId): bool
    {
        $count = $this->cartItemRepository->deleteByCart($cartId);
        return $count > 0;
    }

    public function getCartSummary(int $cartId): array
    {
        $cart = $this->getCartWithItems($cartId);
        
        $subtotal = $cart->subtotal;
        $totalItems = $cart->total_items;
        $itemCount = $cart->items->count();

        return [
            'cart_id' => $cart->id,
            'cart_uuid' => $cart->uuid,
            'item_count' => $itemCount,
            'total_items' => $totalItems,
            'subtotal' => $subtotal,
            'formatted_subtotal' => $cart->formatted_subtotal,
            'currency' => 'MT',
            'expires_at' => $cart->expires_at?->toIso8601String(),
            'is_empty' => $cart->isEmpty(),
        ];
    }

    public function mergeGuestCart(string $sessionId, int $userId): Cart
    {
        return DB::transaction(function () use ($sessionId, $userId) {
            $guestCart = $this->cartRepository->getActiveBySession($sessionId);
            $userCart = $this->getOrCreateCart($userId);

            if (!$guestCart || $guestCart->isEmpty()) {
                return $userCart;
            }

            // Merge items from guest cart to user cart
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $this->cartItemRepository->findInCart(
                    $userCart->id,
                    $guestItem->product_id,
                    $guestItem->product_color_id,
                    $guestItem->product_print_area_id
                );

                if ($existingItem) {
                    $newQuantity = $existingItem->quantity + $guestItem->quantity;
                    $this->updateItemQuantity($existingItem->id, $newQuantity);
                } else {
                    $this->cartItemRepository->create([
                        'cart_id' => $userCart->id,
                        'product_id' => $guestItem->product_id,
                        'design_id' => $guestItem->design_id,
                        'product_color_id' => $guestItem->product_color_id,
                        'product_print_area_id' => $guestItem->product_print_area_id,
                        'quantity' => $guestItem->quantity,
                        'unit_price' => $guestItem->unit_price,
                        'design_prompt' => $guestItem->design_prompt,
                        'mockup_url' => $guestItem->mockup_url,
                    ]);
                }
            }

            // Delete guest cart
            $this->cartRepository->delete($guestCart->id);

            return $this->getCartWithItems($userCart->id);
        });
    }

    public function convertToOrderItems(int $cartId): array
    {
        $cart = $this->getCartWithItems($cartId);
        $orderItems = [];

        foreach ($cart->items as $item) {
            $orderItems[] = [
                'product_id' => $item->product_id,
                'design_id' => $item->design_id,
                'product_color_id' => $item->product_color_id,
                'product_print_area_id' => $item->product_print_area_id,
                'product_name' => $item->product->name,
                'product_sku' => $item->product->sku ?? null,
                'color_name' => $item->color->name,
                'color_hex_code' => $item->color->hex_code,
                'print_area_name' => $item->printArea->name,
                'design_prompt' => $item->design_prompt,
                'mockup_url' => $item->mockup_url,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ];
        }

        return $orderItems;
    }

    public function clearExpiredCarts(): int
    {
        return $this->cartRepository->clearExpired();
    }

    public function extendExpiration(int $cartId, int $days = 7): Cart
    {
        return $this->cartRepository->update($cartId, [
            'expires_at' => now()->addDays($days)
        ]);
    }
}
