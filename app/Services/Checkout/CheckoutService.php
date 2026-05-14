<?php

namespace App\Services\Checkout;

use App\Models\Cart\Cart;
use App\Models\Order\Order;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Events\Checkout\CheckoutCompleted;
use App\Events\Checkout\CheckoutFailed;
use App\Mail\OrderConfirmationMail;
use App\Enums\Order\PaymentStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class CheckoutService
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService
    ) {}

    public function processCheckout(array $data): Order
    {
        $cart = $this->validateCart($data['cart_id'] ?? $data['cart_uuid'] ?? null);
        
        return DB::transaction(function () use ($cart, $data) {
            try {
                // Calculate totals
                $totals = $this->calculateTotals($cart, $data);
                
                // Create order
                $order = $this->createOrderFromCart($cart, $data, $totals);
                
                // Clear the cart
                $this->cartService->clearCart($cart->id);
                
                // Send confirmation email
                $this->sendConfirmationEmail($order, $data);
                
                // Dispatch event
                event(new CheckoutCompleted($order, $data));
                
                Log::info('Checkout completed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total_amount,
                ]);
                
                return $order;
                
            } catch (Exception $e) {
                event(new CheckoutFailed($cart->id, $e->getMessage(), [
                    'data' => $data,
                    'exception' => $e->getTraceAsString(),
                ]));
                
                Log::error('Checkout failed', [
                    'cart_id' => $cart->id,
                    'error' => $e->getMessage(),
                ]);
                
                throw $e;
            }
        });
    }

    protected function validateCart($cartIdentifier): Cart
    {
        if (empty($cartIdentifier)) {
            throw new Exception('Cart identifier is required');
        }

        $cart = is_numeric($cartIdentifier)
            ? $this->cartService->getCart($cartIdentifier)
            : $this->cartService->getCartByUuid($cartIdentifier);

        if (!$cart) {
            throw new Exception('Cart not found');
        }

        if ($cart->isEmpty()) {
            throw new Exception('Cart is empty');
        }

        if ($cart->isExpired()) {
            throw new Exception('Cart has expired');
        }

        // Validate minimum quantities
        foreach ($cart->items as $item) {
            if ($item->product && $item->quantity < $item->product->min_quantity) {
                throw new Exception(
                    "Quantidade mínima para {$item->product->name} é {$item->product->min_quantity}"
                );
            }
        }

        return $cart;
    }

    protected function calculateTotals(Cart $cart, array $data): array
    {
        $subtotal = $cart->subtotal;
        $shippingCost = $this->calculateShipping($cart, $data);
        $taxAmount = $this->calculateTax($subtotal);
        $discountAmount = $data['discount_amount'] ?? 0;
        
        $total = $subtotal + $shippingCost + $taxAmount - $discountAmount;
        
        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax_amount' => $taxAmount,
            'discount_amount' => $discountAmount,
            'total_amount' => max(0, $total),
        ];
    }

    protected function calculateShipping(Cart $cart, array $data): float
    {
        // Simple shipping calculation based on city
        $city = strtolower($data['shipping_city'] ?? 'maputo');
        
        // Free shipping for Maputo city orders over 5000 MT
        if ($city === 'maputo' && $cart->subtotal >= 5000) {
            return 0;
        }
        
        // Base shipping rates
        $rates = [
            'maputo' => 150,
            'matola' => 250,
            'beira' => 500,
            'nampula' => 600,
        ];
        
        return $rates[$city] ?? 350; // Default rate
    }

    protected function calculateTax(float $subtotal): float
    {
        // No VAT for MVP, can be enabled later
        return 0;
    }

    protected function createOrderFromCart(Cart $cart, array $data, array $totals): Order
    {
        $paymentMethod = $data['payment_method'] ?? null;
        
        // Determine payment status based on payment method
        // Mobile money (M-Pesa, Emola) = automatically paid
        // Payment proof upload = pending confirmation
        $paymentStatus = PaymentStatus::PENDING;
        $paidAt = null;
        
        if (in_array(strtolower($paymentMethod ?? ''), ['mpesa', 'emola'])) {
            // Mobile money payments are automatically marked as paid
            $paymentStatus = PaymentStatus::PAID;
            $paidAt = now();
            Log::info('Order marked as paid automatically (mobile money)', [
                'payment_method' => $paymentMethod,
                'cart_id' => $cart->id
            ]);
        } elseif (strtolower($paymentMethod ?? '') === 'proof_upload' || empty($paymentMethod)) {
            // Payment proof uploads wait for manual confirmation
            $paymentStatus = PaymentStatus::PENDING;
            Log::info('Order payment status set to pending (proof upload or no method)', [
                'payment_method' => $paymentMethod,
                'cart_id' => $cart->id
            ]);
        }
        
        // Get user_id from authenticated user if available, otherwise use cart user_id
        // Try to authenticate user from token in request (even without auth middleware)
        $user = null;
        try {
            // Try to get token from request
            $token = request()->bearerToken() ?? request()->header('Authorization');
            if ($token) {
                // Remove 'Bearer ' prefix if present
                $token = str_replace('Bearer ', '', $token);
                // Set token and try to authenticate
                JWTAuth::setToken($token);
                $user = JWTAuth::authenticate();
            }
        } catch (\Exception $e) {
            // Token invalid or not present - that's okay for guest checkout
            Log::debug('No valid auth token for checkout', ['error' => $e->getMessage()]);
        }
        
        $userId = $user ? $user->id : ($cart->user_id ?? null);
        
        // Log for debugging
        Log::info('Creating order with user_id', [
            'authenticated_user_id' => $user ? $user->id : null,
            'cart_user_id' => $cart->user_id ?? null,
            'final_user_id' => $userId,
            'has_auth_user' => $user !== null,
            'has_token' => !empty($token ?? null)
        ]);
        
        $orderData = array_merge([
            'session_id' => $cart->session_id ?? session()->getId(),
            'shipping_name' => $data['shipping_name'],
            'shipping_address' => $data['shipping_address'],
            'shipping_city' => $data['shipping_city'] ?? 'Maputo',
            'shipping_state' => $data['shipping_state'] ?? 'Maputo',
            'shipping_postal_code' => $data['shipping_postal_code'] ?? '',
            'shipping_country' => $data['shipping_country'] ?? 'Moçambique',
            'shipping_phone' => $data['shipping_phone'] ?? '',
            'shipping_whatsapp' => $data['shipping_whatsapp'],
            'billing_name' => $data['billing_name'] ?? $data['shipping_name'],
            'billing_email' => $data['billing_email'],
            'notes' => $data['notes'] ?? null,
            'currency' => 'MT',
            // Construction briefing (kept nullable for backwards compatibility)
            'project_type' => $data['project_type'] ?? null,
            'service_type' => $data['service_type'] ?? null,
            'terrain_area_sqm' => $data['terrain_area_sqm'] ?? null,
            'terrain_location' => $data['terrain_location'] ?? null,
            'terrain_type' => $data['terrain_type'] ?? null,
            'budget_target' => $data['budget_target'] ?? null,
            'desired_deadline' => $data['desired_deadline'] ?? null,
            'style_preferences' => $data['style_preferences'] ?? null,
            'floors_count' => $data['floors_count'] ?? null,
            'rooms_count' => $data['rooms_count'] ?? null,
            'technical_requirements' => $data['technical_requirements'] ?? null,
            'briefing_metadata' => $data['briefing_metadata'] ?? null,
            'briefing_attachments' => $data['briefing_attachments'] ?? null,
            // Payment information
            'payment_method' => $paymentMethod,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_transaction_id' => $data['payment_transaction_id'] ?? null,
            'payment_status' => $paymentStatus,
            'paid_at' => $paidAt,
        ], $totals);
        
        // Ensure user_id is set after all merges to prevent it from being overwritten
        $orderData['user_id'] = $userId;
        
        // Log final orderData to verify user_id is included
        Log::info('Final orderData before creation', [
            'user_id' => $orderData['user_id'] ?? 'NOT SET',
            'has_user_id' => isset($orderData['user_id']) && !empty($orderData['user_id'])
        ]);

        // Convert cart items to order items format
        $items = [];
        foreach ($cart->items as $cartItem) {
            $items[] = [
                'product_id' => $cartItem->product_id,
                'design_id' => $cartItem->design_id,
                'product_color_id' => $cartItem->product_color_id,
                'product_print_area_id' => $cartItem->product_print_area_id,
                'product_name' => $cartItem->product->name ?? 'Produto',
                'product_sku' => $cartItem->product->sku ?? '',
                'color_name' => $cartItem->color->name ?? '',
                'color_hex_code' => $cartItem->color->hex_code ?? '#000000',
                'print_area_name' => $cartItem->printArea->name ?? '',
                'design_prompt' => $cartItem->design_prompt,
                'mockup_url' => $cartItem->mockup_url,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
            ];
        }

        // Use OrderService::create() which handles both order and items creation
        return $this->orderService->create($orderData, $items);
    }

    protected function sendConfirmationEmail(Order $order, array $data): void
    {
        $email = $data['billing_email'];
        
        if (empty($email)) {
            Log::warning('No email provided for order confirmation', [
                'order_id' => $order->id,
            ]);
            return;
        }

        try {
            Mail::to($email)->send(new OrderConfirmationMail($order));
            
            // Also send to admin
            $adminEmail = config('mail.admin_address', 'mikemiranda.m2@gmail.com');
            Mail::to($adminEmail)->send(new OrderConfirmationMail($order));
            
            Log::info('Order confirmation email sent', [
                'order_id' => $order->id,
                'email' => $email,
            ]);
        } catch (Exception $e) {
            Log::error('Failed to send order confirmation email', [
                'order_id' => $order->id,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - email failure shouldn't block order
        }
    }

    public function getCheckoutSummary(string $cartUuid): array
    {
        $cart = $this->cartService->getCartByUuid($cartUuid);
        
        if (!$cart) {
            throw new Exception('Cart not found');
        }

        $cart->load(['items.product', 'items.color', 'items.printArea']);

        return [
            'cart_uuid' => $cart->uuid,
            'items' => $cart->items->map(function ($item) {
                return [
                    'id' => $item->uuid,
                    'product_name' => $item->product->name ?? 'Produto',
                    'color_name' => $item->color->name ?? '',
                    'print_area_name' => $item->printArea->name ?? '',
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'formatted_unit_price' => $item->formatted_unit_price,
                    'formatted_total' => $item->formatted_total,
                    'mockup_url' => $item->mockup_url,
                    'min_quantity' => $item->product->min_quantity ?? 1,
                ];
            }),
            'subtotal' => $cart->subtotal,
            'formatted_subtotal' => $cart->formatted_subtotal,
            'total_items' => $cart->total_items,
            'shipping_options' => $this->getShippingOptions($cart),
        ];
    }

    protected function getShippingOptions(Cart $cart): array
    {
        $options = [
            [
                'id' => 'maputo',
                'name' => 'Maputo Cidade',
                'price' => $cart->subtotal >= 5000 ? 0 : 150,
                'formatted_price' => $cart->subtotal >= 5000 ? 'Grátis' : '150,00 MT',
                'delivery_time' => '1-2 dias úteis',
            ],
            [
                'id' => 'matola',
                'name' => 'Matola',
                'price' => 250,
                'formatted_price' => '250,00 MT',
                'delivery_time' => '2-3 dias úteis',
            ],
            [
                'id' => 'other',
                'name' => 'Outras Províncias',
                'price' => 500,
                'formatted_price' => '500,00 MT',
                'delivery_time' => '5-7 dias úteis',
            ],
        ];

        return $options;
    }

    public function validateCheckoutData(array $data): array
    {
        $errors = [];

        if (empty($data['shipping_name'])) {
            $errors['shipping_name'] = 'Nome completo é obrigatório';
        }

        if (empty($data['shipping_address'])) {
            $errors['shipping_address'] = 'Endereço é obrigatório';
        }

        if (empty($data['shipping_whatsapp'])) {
            $errors['shipping_whatsapp'] = 'WhatsApp é obrigatório';
        } elseif (!preg_match('/^\+258[0-9]{9}$/', $data['shipping_whatsapp'])) {
            $errors['shipping_whatsapp'] = 'WhatsApp deve estar no formato +258XXXXXXXXX';
        }

        if (empty($data['billing_email'])) {
            $errors['billing_email'] = 'Email é obrigatório';
        } elseif (!filter_var($data['billing_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['billing_email'] = 'Email inválido';
        }

        return $errors;
    }
}
