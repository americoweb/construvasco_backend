#!/bin/bash

# iOPS Amazing Brindes - Checkout Module Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "💳 Setting up Amazing Brindes Checkout Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directory structure
echo "📁 Creating directory structure..."

mkdir -p app/Http/Controllers/Checkout
mkdir -p app/Services/Checkout
mkdir -p app/Http/Requests/Checkout
mkdir -p app/Http/Resources/Checkout
mkdir -p app/Events/Checkout
mkdir -p app/Jobs/Checkout
mkdir -p app/Mail

echo "✅ Directory structure created!"

# ============================================
# EVENTS
# ============================================
echo "📡 Creating Events..."

cat > app/Events/Checkout/CheckoutCompleted.php << 'EOF'
<?php

namespace App\Events\Checkout;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order\Order;

class CheckoutCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order,
        public array $checkoutData = []
    ) {}
}
EOF

cat > app/Events/Checkout/CheckoutFailed.php << 'EOF'
<?php

namespace App\Events\Checkout;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CheckoutFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $cartId,
        public string $reason,
        public array $context = []
    ) {}
}
EOF

# ============================================
# MAIL
# ============================================
echo "📧 Creating Mail Classes..."

cat > app/Mail/OrderConfirmationMail.php << 'EOF'
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Order\Order;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Confirmação do Pedido #{$this->order->order_number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.orders.confirmation',
            with: [
                'order' => $this->order,
                'items' => $this->order->items,
                'formattedTotal' => $this->order->formatted_total,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
EOF

# Create email template
mkdir -p resources/views/emails/orders

cat > resources/views/emails/orders/confirmation.blade.php << 'EOF'
@component('mail::message')
# Confirmação do Pedido

Obrigado pelo seu pedido!

**Número do Pedido:** {{ $order->order_number }}
**Data:** {{ $order->created_at->format('d/m/Y H:i') }}

## Itens do Pedido

@component('mail::table')
| Produto | Cor | Quantidade | Preço |
|:--------|:----|:-----------|------:|
@foreach($items as $item)
| {{ $item->product_name }} | {{ $item->color_name }} | {{ $item->quantity }} | {{ number_format($item->total_price, 2, ',', '.') }} MT |
@endforeach
@endcomponent

**Subtotal:** {{ number_format($order->subtotal, 2, ',', '.') }} MT
@if($order->shipping_cost > 0)
**Frete:** {{ number_format($order->shipping_cost, 2, ',', '.') }} MT
@endif
@if($order->discount_amount > 0)
**Desconto:** -{{ number_format($order->discount_amount, 2, ',', '.') }} MT
@endif
**Total:** {{ $formattedTotal }}

## Endereço de Entrega

{{ $order->shipping_name }}
{{ $order->shipping_address }}
{{ $order->shipping_city }}, {{ $order->shipping_state }}
{{ $order->shipping_country }}
WhatsApp: {{ $order->shipping_whatsapp }}

@component('mail::button', ['url' => config('app.url') . '/orders/' . $order->uuid])
Ver Pedido
@endcomponent

Entraremos em contacto pelo WhatsApp para confirmar os detalhes.

Obrigado,<br>
{{ config('app.name') }}
@endcomponent
EOF

# ============================================
# SERVICE
# ============================================
echo "⚙️ Creating Service..."

cat > app/Services/Checkout/CheckoutService.php << 'EOF'
<?php

namespace App\Services\Checkout;

use App\Models\Cart\Cart;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Events\Checkout\CheckoutCompleted;
use App\Events\Checkout\CheckoutFailed;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
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
        $orderData = array_merge([
            'user_id' => $cart->user_id,
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
        ], $totals);

        $order = $this->orderService->createOrder($orderData);

        // Create order items from cart items
        foreach ($cart->items as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
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
                'total_price' => $cartItem->total_price,
            ]);
        }

        return $order->fresh(['items']);
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
EOF

# ============================================
# FORM REQUESTS
# ============================================
echo "📝 Creating Form Requests..."

cat > app/Http/Requests/Checkout/ProcessCheckoutRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_id' => 'required_without:cart_uuid|integer|exists:carts,id',
            'cart_uuid' => 'required_without:cart_id|string|uuid|exists:carts,uuid',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'sometimes|string|max:100',
            'shipping_state' => 'sometimes|string|max:100',
            'shipping_postal_code' => 'sometimes|string|max:20',
            'shipping_country' => 'sometimes|string|max:100',
            'shipping_phone' => 'sometimes|string|max:20',
            'shipping_whatsapp' => 'required|string|regex:/^\+258[0-9]{9}$/',
            'billing_name' => 'sometimes|string|max:255',
            'billing_email' => 'required|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'discount_amount' => 'sometimes|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_id.required_without' => 'Cart ID ou UUID é obrigatório',
            'cart_uuid.required_without' => 'Cart UUID ou ID é obrigatório',
            'cart_id.exists' => 'Carrinho não encontrado',
            'cart_uuid.exists' => 'Carrinho não encontrado',
            'shipping_name.required' => 'Nome completo é obrigatório',
            'shipping_address.required' => 'Endereço é obrigatório',
            'shipping_whatsapp.required' => 'WhatsApp é obrigatório',
            'shipping_whatsapp.regex' => 'WhatsApp deve estar no formato +258XXXXXXXXX',
            'billing_email.required' => 'Email é obrigatório',
            'billing_email.email' => 'Email inválido',
        ];
    }
}
EOF

cat > app/Http/Requests/Checkout/GetCheckoutSummaryRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class GetCheckoutSummaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cart_uuid' => 'required|string|uuid|exists:carts,uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'cart_uuid.required' => 'Cart UUID é obrigatório',
            'cart_uuid.uuid' => 'UUID inválido',
            'cart_uuid.exists' => 'Carrinho não encontrado',
        ];
    }
}
EOF

# ============================================
# API RESOURCES
# ============================================
echo "📦 Creating API Resources..."

cat > app/Http/Resources/Checkout/CheckoutSummaryResource.php << 'EOF'
<?php

namespace App\Http\Resources\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_uuid' => $this->resource['cart_uuid'],
            'items' => $this->resource['items'],
            'subtotal' => $this->resource['subtotal'],
            'formatted_subtotal' => $this->resource['formatted_subtotal'],
            'total_items' => $this->resource['total_items'],
            'shipping_options' => $this->resource['shipping_options'],
        ];
    }
}
EOF

cat > app/Http/Resources/Checkout/CheckoutResultResource.php << 'EOF'
<?php

namespace App\Http\Resources\Checkout;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CheckoutResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'order_id' => $this->uuid,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'subtotal' => $this->subtotal,
            'shipping_cost' => $this->shipping_cost,
            'tax_amount' => $this->tax_amount,
            'discount_amount' => $this->discount_amount,
            'total_amount' => $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'shipping' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'country' => $this->shipping_country,
                'whatsapp' => $this->shipping_whatsapp,
            ],
            'billing_email' => $this->billing_email,
            'items_count' => $this->items->count(),
            'items' => $this->items->map(function ($item) {
                return [
                    'product_name' => $item->product_name,
                    'color_name' => $item->color_name,
                    'print_area_name' => $item->print_area_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                    'mockup_url' => $item->mockup_url,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

# ============================================
# CONTROLLER
# ============================================
echo "🎮 Creating Controller..."

cat > app/Http/Controllers/Checkout/CheckoutController.php << 'EOF'
<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Services\Checkout\CheckoutService;
use App\Http\Requests\Checkout\ProcessCheckoutRequest;
use App\Http\Resources\Checkout\CheckoutSummaryResource;
use App\Http\Resources\Checkout\CheckoutResultResource;
use Illuminate\Http\JsonResponse;
use Exception;

class CheckoutController extends Controller
{
    public function __construct(
        private CheckoutService $checkoutService
    ) {}

    public function getSummary(string $cartUuid): JsonResponse
    {
        try {
            $summary = $this->checkoutService->getCheckoutSummary($cartUuid);
            
            return response()->json([
                'data' => new CheckoutSummaryResource($summary)
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function process(ProcessCheckoutRequest $request): JsonResponse
    {
        try {
            $order = $this->checkoutService->processCheckout($request->validated());
            
            return response()->json([
                'data' => new CheckoutResultResource($order),
                'message' => 'Pedido criado com sucesso!'
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'CHECKOUT_FAILED'
            ], 422);
        }
    }

    public function validateData(): JsonResponse
    {
        $data = request()->all();
        $errors = $this->checkoutService->validateCheckoutData($data);
        
        if (!empty($errors)) {
            return response()->json([
                'valid' => false,
                'errors' => $errors
            ], 422);
        }
        
        return response()->json([
            'valid' => true,
            'message' => 'Dados válidos'
        ]);
    }

    public function getShippingOptions(string $cartUuid): JsonResponse
    {
        try {
            $summary = $this->checkoutService->getCheckoutSummary($cartUuid);
            
            return response()->json([
                'data' => $summary['shipping_options']
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔧 Creating Service Provider..."

cat > app/Providers/CheckoutServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Checkout\CheckoutService;

class CheckoutServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CheckoutService::class, function ($app) {
            return new CheckoutService(
                $app->make(\App\Services\Cart\CartService::class),
                $app->make(\App\Services\Order\OrderService::class)
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
EOF

# ============================================
# ROUTES
# ============================================
echo "🛤️ Creating Routes..."

cat > routes/checkout.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Checkout\CheckoutController;

Route::prefix('api/v1/checkout')->group(function () {
    // Get checkout summary for a cart
    Route::get('summary/{cartUuid}', [CheckoutController::class, 'getSummary']);
    
    // Get shipping options
    Route::get('shipping/{cartUuid}', [CheckoutController::class, 'getShippingOptions']);
    
    // Validate checkout data before submitting
    Route::post('validate', [CheckoutController::class, 'validateData']);
    
    // Process checkout - create order from cart
    Route::post('process', [CheckoutController::class, 'process']);
});
EOF

# ============================================
# JOBS
# ============================================
echo "⏰ Creating Jobs..."

cat > app/Jobs/Checkout/SendOrderNotification.php << 'EOF'
<?php

namespace App\Jobs\Checkout;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Log;

class SendOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function handle(): void
    {
        // Send WhatsApp notification (integrate with WhatsApp Business API)
        $whatsapp = $this->order->shipping_whatsapp;
        $message = $this->buildMessage();
        
        // TODO: Integrate with actual WhatsApp Business API
        Log::info('WhatsApp notification would be sent', [
            'order_id' => $this->order->id,
            'whatsapp' => $whatsapp,
            'message' => $message,
        ]);
    }

    protected function buildMessage(): string
    {
        return "Olá {$this->order->shipping_name}! 🎉\n\n"
            . "Seu pedido #{$this->order->order_number} foi recebido com sucesso!\n\n"
            . "Total: {$this->order->formatted_total}\n\n"
            . "Entraremos em contacto em breve para confirmar os detalhes.\n\n"
            . "Obrigado por escolher Amazing Brindes!";
    }
}
EOF

# ============================================
# EVENT LISTENERS
# ============================================
echo "👂 Creating Event Listeners..."

mkdir -p app/Listeners/Checkout

cat > app/Listeners/Checkout/SendCheckoutNotifications.php << 'EOF'
<?php

namespace App\Listeners\Checkout;

use App\Events\Checkout\CheckoutCompleted;
use App\Jobs\Checkout\SendOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendCheckoutNotifications implements ShouldQueue
{
    public function handle(CheckoutCompleted $event): void
    {
        // Dispatch WhatsApp notification job
        SendOrderNotification::dispatch($event->order)->delay(now()->addSeconds(30));
    }
}
EOF

cat > app/Listeners/Checkout/LogCheckoutFailure.php << 'EOF'
<?php

namespace App\Listeners\Checkout;

use App\Events\Checkout\CheckoutFailed;
use Illuminate\Support\Facades\Log;

class LogCheckoutFailure
{
    public function handle(CheckoutFailed $event): void
    {
        Log::channel('checkout')->error('Checkout failed', [
            'cart_id' => $event->cartId,
            'reason' => $event->reason,
            'context' => $event->context,
        ]);
    }
}
EOF

# ============================================
# CONFIG
# ============================================
echo "⚙️ Creating Config..."

mkdir -p config

cat > config/checkout.php << 'EOF'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Checkout Settings
    |--------------------------------------------------------------------------
    */
    
    'currency' => env('CHECKOUT_CURRENCY', 'MT'),
    
    'free_shipping_threshold' => env('FREE_SHIPPING_THRESHOLD', 5000),
    
    'default_city' => env('CHECKOUT_DEFAULT_CITY', 'Maputo'),
    
    'default_country' => env('CHECKOUT_DEFAULT_COUNTRY', 'Moçambique'),
    
    /*
    |--------------------------------------------------------------------------
    | Shipping Rates
    |--------------------------------------------------------------------------
    */
    
    'shipping_rates' => [
        'maputo' => 150,
        'matola' => 250,
        'beira' => 500,
        'nampula' => 600,
        'default' => 350,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Admin Notifications
    |--------------------------------------------------------------------------
    */
    
    'admin_email' => env('CHECKOUT_ADMIN_EMAIL', 'mikemiranda.m2@gmail.com'),
    
    'admin_whatsapp' => env('CHECKOUT_ADMIN_WHATSAPP', '+258849999999'),
    
    /*
    |--------------------------------------------------------------------------
    | Tax Settings
    |--------------------------------------------------------------------------
    */
    
    'tax_enabled' => env('CHECKOUT_TAX_ENABLED', false),
    
    'tax_rate' => env('CHECKOUT_TAX_RATE', 0),
];
EOF

# ============================================
# TESTS
# ============================================
echo "🧪 Creating Tests..."

mkdir -p tests/Feature/Checkout
mkdir -p tests/Unit/Services/Checkout

cat > tests/Feature/Checkout/CheckoutTest.php << 'EOF'
<?php

namespace Tests\Feature\Checkout;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Product\Product;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_checkout_summary(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

        $response = $this->getJson("/api/v1/checkout/summary/{$cart->uuid}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'cart_uuid',
                         'items',
                         'subtotal',
                         'formatted_subtotal',
                         'total_items',
                         'shipping_options',
                     ]
                 ]);
    }

    public function test_can_process_checkout(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_city' => 'Maputo',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'data' => [
                         'order_id',
                         'order_number',
                         'status',
                         'total_amount',
                     ],
                     'message'
                 ]);
    }

    public function test_cannot_checkout_empty_cart(): void
    {
        $cart = Cart::factory()->create();

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(422);
    }

    public function test_validates_whatsapp_format(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id]);

        $checkoutData = [
            'cart_uuid' => $cart->uuid,
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '841234567', // Invalid format
            'billing_email' => 'joao@example.com',
        ];

        $response = $this->postJson('/api/v1/checkout/process', $checkoutData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['shipping_whatsapp']);
    }

    public function test_can_get_shipping_options(): void
    {
        $cart = Cart::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id]);

        $response = $this->getJson("/api/v1/checkout/shipping/{$cart->uuid}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'price',
                             'formatted_price',
                             'delivery_time',
                         ]
                     ]
                 ]);
    }
}
EOF

cat > tests/Unit/Services/Checkout/CheckoutServiceTest.php << 'EOF'
<?php

namespace Tests\Unit\Services\Checkout;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Checkout\CheckoutService;
use App\Services\Cart\CartService;
use App\Services\Order\OrderService;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use Mockery;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CheckoutService::class);
    }

    public function test_validates_checkout_data(): void
    {
        $validData = [
            'shipping_name' => 'João Silva',
            'shipping_address' => 'Av. Eduardo Mondlane, 123',
            'shipping_whatsapp' => '+258841234567',
            'billing_email' => 'joao@example.com',
        ];

        $errors = $this->service->validateCheckoutData($validData);

        $this->assertEmpty($errors);
    }

    public function test_returns_errors_for_invalid_data(): void
    {
        $invalidData = [
            'shipping_name' => '',
            'shipping_whatsapp' => '841234567', // Wrong format
            'billing_email' => 'invalid-email',
        ];

        $errors = $this->service->validateCheckoutData($invalidData);

        $this->assertArrayHasKey('shipping_name', $errors);
        $this->assertArrayHasKey('shipping_address', $errors);
        $this->assertArrayHasKey('shipping_whatsapp', $errors);
        $this->assertArrayHasKey('billing_email', $errors);
    }
}
EOF

# ============================================
# FINAL OUTPUT
# ============================================
echo ""
echo "✅ Amazing Brindes Checkout Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 2 Events (CheckoutCompleted, CheckoutFailed)"
echo "   - 1 Mail Class (OrderConfirmationMail)"
echo "   - 1 Service (CheckoutService)"
echo "   - 2 Form Requests"
echo "   - 2 API Resources"
echo "   - 1 Controller"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 1 Job (SendOrderNotification)"
echo "   - 2 Event Listeners"
echo "   - 1 Config file"
echo "   - 1 Email Template"
echo "   - 2 Test files"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Register the Service Provider in config/app.php:"
echo "      App\\Providers\\CheckoutServiceProvider::class,"
echo ""
echo "   2. Register the routes in routes/api.php:"
echo "      require __DIR__.'/checkout.php';"
echo ""
echo "   3. Register Event Listeners in app/Providers/EventServiceProvider.php:"
echo "      protected \$listen = ["
echo "          CheckoutCompleted::class => ["
echo "              SendCheckoutNotifications::class,"
echo "          ],"
echo "          CheckoutFailed::class => ["
echo "              LogCheckoutFailure::class,"
echo "          ],"
echo "      ];"
echo ""
echo "   4. Create checkout log channel in config/logging.php:"
echo "      'checkout' => ["
echo "          'driver' => 'daily',"
echo "          'path' => storage_path('logs/checkout.log'),"
echo "          'level' => 'debug',"
echo "          'days' => 14,"
echo "      ],"
echo ""
echo "   5. Run tests:"
echo "      php artisan test --filter=Checkout"
echo ""
echo "📋 API Endpoints Created:"
echo "   GET  /api/v1/checkout/summary/{cartUuid}   - Get checkout summary"
echo "   GET  /api/v1/checkout/shipping/{cartUuid}  - Get shipping options"
echo "   POST /api/v1/checkout/validate             - Validate checkout data"
echo "   POST /api/v1/checkout/process              - Process checkout"
echo ""
echo "💡 Features:"
echo "   - Cart to Order conversion"
echo "   - Shipping cost calculation"
echo "   - Free shipping for Maputo orders over 5000 MT"
echo "   - Email confirmation (customer + admin)"
echo "   - WhatsApp notification (ready for integration)"
echo "   - Checkout validation"
echo "   - Event-driven architecture"
echo ""
echo "🎉 Checkout Module ready for Amazing Brindes!"
