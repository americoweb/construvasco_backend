#!/bin/bash

# iOPS Amazing Brindes - Cart Module Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "🛍️ Setting up Amazing Brindes Cart Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directory structure
echo "📁 Creating directory structure..."

mkdir -p app/Http/Controllers/Cart
mkdir -p app/Services/Cart
mkdir -p app/Repositories/Cart/Contracts
mkdir -p app/Repositories/Cart/Eloquent
mkdir -p app/Models/Cart
mkdir -p app/Http/Requests/Cart
mkdir -p app/Http/Resources/Cart
mkdir -p database/migrations
mkdir -p database/factories/Cart
mkdir -p database/seeders

echo "✅ Directory structure created!"

# ============================================
# MODELS
# ============================================
echo "📦 Creating Models..."

cat > app/Models/Cart/Cart.php << 'EOF'
<?php

namespace App\Models\Cart;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasUuid;

class Cart extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'session_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('created_at', 'desc');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getSubtotalAttribute(): float
    {
        return $this->items->sum('total_price');
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return number_format($this->subtotal, 2, ',', '.') . ' MT';
    }
}
EOF

cat > app/Models/Cart/CartItem.php << 'EOF'
<?php

namespace App\Models\Cart;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Models\Design\Design;
use App\Traits\HasUuid;

class CartItem extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'design_id',
        'product_color_id',
        'product_print_area_id',
        'quantity',
        'unit_price',
        'total_price',
        'design_prompt',
        'mockup_url',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });
        
        static::updating(function ($item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(ProductColor::class, 'product_color_id');
    }

    public function printArea(): BelongsTo
    {
        return $this->belongsTo(ProductPrintArea::class, 'product_print_area_id');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_price, 2, ',', '.') . ' MT';
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2, ',', '.') . ' MT';
    }

    public function updateQuantity(int $quantity): void
    {
        $this->quantity = $quantity;
        $this->total_price = $this->unit_price * $quantity;
        $this->save();
    }
}
EOF

# ============================================
# REPOSITORY CONTRACTS
# ============================================
echo "📋 Creating Repository Contracts..."

cat > app/Repositories/Cart/Contracts/CartRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Cart\Contracts;

use App\Models\Cart\Cart;

interface CartRepositoryInterface
{
    public function findById(int $id): ?Cart;
    
    public function findByUuid(string $uuid): ?Cart;
    
    public function findByUser(int $userId): ?Cart;
    
    public function findBySession(string $sessionId): ?Cart;
    
    public function create(array $data): Cart;
    
    public function update(int $id, array $data): Cart;
    
    public function delete(int $id): bool;
    
    public function getActiveByUser(int $userId): ?Cart;
    
    public function getActiveBySession(string $sessionId): ?Cart;
    
    public function getWithItems(int $id): ?Cart;
    
    public function clearExpired(): int;
}
EOF

cat > app/Repositories/Cart/Contracts/CartItemRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Cart\Contracts;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemRepositoryInterface
{
    public function findById(int $id): ?CartItem;
    
    public function findByUuid(string $uuid): ?CartItem;
    
    public function create(array $data): CartItem;
    
    public function update(int $id, array $data): CartItem;
    
    public function delete(int $id): bool;
    
    public function getByCart(int $cartId): Collection;
    
    public function findInCart(int $cartId, int $productId, int $colorId, int $printAreaId): ?CartItem;
    
    public function deleteByCart(int $cartId): int;
}
EOF

# ============================================
# REPOSITORY IMPLEMENTATIONS
# ============================================
echo "🗃️ Creating Repository Implementations..."

cat > app/Repositories/Cart/Eloquent/EloquentCartRepository.php << 'EOF'
<?php

namespace App\Repositories\Cart\Eloquent;

use App\Models\Cart\Cart;
use App\Repositories\Cart\Contracts\CartRepositoryInterface;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function __construct(
        protected Cart $model
    ) {}

    public function findById(int $id): ?Cart
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Cart
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByUser(int $userId): ?Cart
    {
        return $this->model->byUser($userId)->first();
    }

    public function findBySession(string $sessionId): ?Cart
    {
        return $this->model->bySession($sessionId)->first();
    }

    public function create(array $data): Cart
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Cart
    {
        $cart = $this->findById($id);
        $cart->update($data);
        return $cart->fresh();
    }

    public function delete(int $id): bool
    {
        $cart = $this->findById($id);
        return $cart->delete();
    }

    public function getActiveByUser(int $userId): ?Cart
    {
        return $this->model->byUser($userId)
            ->active()
            ->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->first();
    }

    public function getActiveBySession(string $sessionId): ?Cart
    {
        return $this->model->bySession($sessionId)
            ->active()
            ->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->first();
    }

    public function getWithItems(int $id): ?Cart
    {
        return $this->model->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->find($id);
    }

    public function clearExpired(): int
    {
        return $this->model->where('expires_at', '<', now())->delete();
    }
}
EOF

cat > app/Repositories/Cart/Eloquent/EloquentCartItemRepository.php << 'EOF'
<?php

namespace App\Repositories\Cart\Eloquent;

use App\Models\Cart\CartItem;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCartItemRepository implements CartItemRepositoryInterface
{
    public function __construct(
        protected CartItem $model
    ) {}

    public function findById(int $id): ?CartItem
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?CartItem
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): CartItem
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): CartItem
    {
        $item = $this->findById($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): bool
    {
        $item = $this->findById($id);
        return $item->delete();
    }

    public function getByCart(int $cartId): Collection
    {
        return $this->model->where('cart_id', $cartId)
            ->with(['product', 'color', 'printArea', 'design'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findInCart(int $cartId, int $productId, int $colorId, int $printAreaId): ?CartItem
    {
        return $this->model->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('product_color_id', $colorId)
            ->where('product_print_area_id', $printAreaId)
            ->first();
    }

    public function deleteByCart(int $cartId): int
    {
        return $this->model->where('cart_id', $cartId)->delete();
    }
}
EOF

# ============================================
# SERVICES
# ============================================
echo "⚙️ Creating Services..."

cat > app/Services/Cart/CartService.php << 'EOF'
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
EOF

# ============================================
# HTTP REQUESTS
# ============================================
echo "✅ Creating Form Requests..."

cat > app/Http/Requests/Cart/AddToCartRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|exists:products,id',
            'design_id' => 'nullable|exists:designs,id',
            'product_color_id' => 'required|exists:product_colors,id',
            'product_print_area_id' => 'required|exists:product_print_areas,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'design_prompt' => 'nullable|string|max:2000',
            'mockup_url' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'O produto é obrigatório',
            'product_id.exists' => 'Produto não encontrado',
            'product_color_id.required' => 'A cor é obrigatória',
            'product_print_area_id.required' => 'A área de impressão é obrigatória',
            'quantity.required' => 'A quantidade é obrigatória',
            'quantity.min' => 'A quantidade mínima é 1',
            'unit_price.required' => 'O preço unitário é obrigatório',
        ];
    }
}
EOF

cat > app/Http/Requests/Cart/UpdateCartItemRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.required' => 'A quantidade é obrigatória',
            'quantity.min' => 'A quantidade mínima é 1',
        ];
    }
}
EOF

# ============================================
# HTTP RESOURCES
# ============================================
echo "📤 Creating API Resources..."

cat > app/Http/Resources/Cart/CartResource.php << 'EOF'
<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'item_count' => $this->items->count(),
            'total_items' => $this->total_items,
            'subtotal' => (float) $this->subtotal,
            'formatted_subtotal' => $this->formatted_subtotal,
            'currency' => 'MT',
            'is_empty' => $this->isEmpty(),
            'is_expired' => $this->isExpired(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Cart/CartItemResource.php << 'EOF'
<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product_id' => $this->product_id,
            'design_id' => $this->design_id,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
                'image_url' => $this->product->image_url,
                'min_quantity' => $this->product->min_quantity,
            ],
            'color' => [
                'id' => $this->color->id,
                'name' => $this->color->name,
                'hex_code' => $this->color->hex_code,
            ],
            'print_area' => [
                'id' => $this->printArea->id,
                'name' => $this->printArea->name,
            ],
            'design_prompt' => $this->design_prompt,
            'mockup_url' => $this->mockup_url,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'total_price' => (float) $this->total_price,
            'formatted_total' => $this->formatted_total,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Cart/CartSummaryResource.php << 'EOF'
<?php

namespace App\Http\Resources\Cart;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'cart_id' => $this['cart_id'],
            'cart_uuid' => $this['cart_uuid'],
            'item_count' => $this['item_count'],
            'total_items' => $this['total_items'],
            'subtotal' => (float) $this['subtotal'],
            'formatted_subtotal' => $this['formatted_subtotal'],
            'currency' => $this['currency'],
            'expires_at' => $this['expires_at'],
            'is_empty' => $this['is_empty'],
        ];
    }
}
EOF

# ============================================
# CONTROLLERS
# ============================================
echo "🎮 Creating Controllers..."

cat > app/Http/Controllers/Cart/CartController.php << 'EOF'
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
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔌 Creating Service Provider..."

cat > app/Providers/CartServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Cart\Contracts\CartRepositoryInterface;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use App\Repositories\Cart\Eloquent\EloquentCartRepository;
use App\Repositories\Cart\Eloquent\EloquentCartItemRepository;

class CartServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CartRepositoryInterface::class,
            EloquentCartRepository::class
        );

        $this->app->bind(
            CartItemRepositoryInterface::class,
            EloquentCartItemRepository::class
        );
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

cat > routes/cart.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cart\CartController;

/*
|--------------------------------------------------------------------------
| Cart API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Cart Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('cart')->group(function () {
        // Get or create cart
        Route::get('/', [CartController::class, 'show']);
        
        // Get cart by ID
        Route::get('/{id}', [CartController::class, 'showById'])->where('id', '[0-9]+');
        
        // Get cart by session
        Route::get('/session/{sessionId}', [CartController::class, 'showBySession']);
        
        // Add item to cart
        Route::post('/items', [CartController::class, 'addItem']);
        
        // Update item quantity
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        
        // Remove item from cart
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        
        // Clear cart
        Route::delete('/{cartId}/clear', [CartController::class, 'clear']);
        
        // Get cart summary
        Route::get('/{cartId}/summary', [CartController::class, 'summary']);
        
        // Convert cart to order items
        Route::get('/{cartId}/checkout-items', [CartController::class, 'convertToOrder']);
        
        // Extend cart expiration
        Route::post('/{cartId}/extend', [CartController::class, 'extendExpiration']);
    });

    // Authenticated cart routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/cart/merge', [CartController::class, 'merge']);
    });
});
EOF

# ============================================
# DATABASE MIGRATIONS
# ============================================
echo "🗄️ Creating Database Migrations..."

CURRENT_TIME=$(date +%s)
TIMESTAMP_1=$(date -d "@$((CURRENT_TIME + 30))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 30)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_2=$(date -d "@$((CURRENT_TIME + 31))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 31)) +%Y_%m_%d_%H%M%S)

cat > database/migrations/${TIMESTAMP_1}_create_carts_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('session_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_2}_create_cart_items_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('design_id')->nullable();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('product_print_area_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('design_prompt')->nullable();
            $table->text('mockup_url')->nullable();
            $table->timestamps();

            $table->index('cart_id');
            $table->index(['cart_id', 'product_id']);

            $table->foreign('cart_id')
                  ->references('id')
                  ->on('carts')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('cascade');

            $table->foreign('design_id')
                  ->references('id')
                  ->on('designs')
                  ->onDelete('set null');

            $table->foreign('product_color_id')
                  ->references('id')
                  ->on('product_colors')
                  ->onDelete('cascade');

            $table->foreign('product_print_area_id')
                  ->references('id')
                  ->on('product_print_areas')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
EOF

# ============================================
# FACTORIES
# ============================================
echo "🏭 Creating Factories..."

cat > database/factories/Cart/CartFactory.php << 'EOF'
<?php

namespace Database\Factories\Cart;

use App\Models\Cart\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CartFactory extends Factory
{
    protected $model = Cart::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'user_id' => null,
            'session_id' => Str::random(32),
            'expires_at' => now()->addDays(7),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    public function forUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
            'session_id' => null,
        ]);
    }
}
EOF

cat > database/factories/Cart/CartItemFactory.php << 'EOF'
<?php

namespace Database\Factories\Cart;

use App\Models\Cart\CartItem;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first();
        $quantity = $this->faker->numberBetween(1, 20);
        $unitPrice = $product?->price ?? 1000;
        
        return [
            'uuid' => Str::uuid(),
            'product_id' => $product?->id ?? 1,
            'design_id' => null,
            'product_color_id' => $product?->colors()->first()?->id ?? 1,
            'product_print_area_id' => $product?->printAreas()->first()?->id ?? 1,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
            'design_prompt' => $this->faker->sentence(10),
            'mockup_url' => $this->faker->imageUrl(640, 480, 'product'),
        ];
    }
}
EOF

# ============================================
# SEEDERS
# ============================================
echo "🌱 Creating Seeders..."

cat > database/seeders/CartSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cart\Cart;
use App\Models\Cart\CartItem;
use App\Models\Product\Product;
use Illuminate\Support\Str;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with(['colors', 'printAreas'])->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        // Create 10 sample carts
        for ($i = 0; $i < 10; $i++) {
            $cart = Cart::create([
                'uuid' => Str::uuid(),
                'session_id' => 'seed_cart_' . Str::random(20),
                'expires_at' => now()->addDays(rand(1, 14)),
            ]);

            // Add 1-5 items to each cart
            $numItems = rand(1, 5);
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                
                if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                    continue;
                }

                $color = $product->colors->random();
                $printArea = $product->printAreas->random();
                $quantity = rand($product->min_quantity, $product->min_quantity * 2);

                CartItem::create([
                    'uuid' => Str::uuid(),
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'product_color_id' => $color->id,
                    'product_print_area_id' => $printArea->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $quantity,
                    'design_prompt' => 'Design personalizado para ' . $product->name,
                    'mockup_url' => 'https://via.placeholder.com/640x480/cccccc/666666?text=' . urlencode($product->name),
                ]);
            }
        }

        // Create some expired carts
        for ($i = 0; $i < 3; $i++) {
            Cart::create([
                'uuid' => Str::uuid(),
                'session_id' => 'seed_expired_' . Str::random(10),
                'expires_at' => now()->subDays(rand(1, 10)),
            ]);
        }
    }
}
EOF

cat > database/seeders/CartModuleSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CartModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CartSeeder::class,
        ]);
    }
}
EOF

# ============================================
# CONSOLE COMMAND
# ============================================
echo "🖥️ Creating Console Command..."

mkdir -p app/Console/Commands

cat > app/Console/Commands/ClearExpiredCarts.php << 'EOF'
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Cart\CartService;

class ClearExpiredCarts extends Command
{
    protected $signature = 'cart:clear-expired';
    
    protected $description = 'Remove expired shopping carts from the database';

    public function handle(CartService $cartService): int
    {
        $count = $cartService->clearExpiredCarts();
        
        $this->info("Removed {$count} expired carts.");
        
        return Command::SUCCESS;
    }
}
EOF

# ============================================
# FINAL OUTPUT
# ============================================
echo ""
echo "✅ Amazing Brindes Cart Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 2 Models (Cart, CartItem)"
echo "   - 2 Repository Contracts"
echo "   - 2 Repository Implementations"
echo "   - 1 Service"
echo "   - 2 Form Requests"
echo "   - 3 API Resources"
echo "   - 1 Controller"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 2 Migrations"
echo "   - 2 Factories"
echo "   - 2 Seeders"
echo "   - 1 Console Command"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Register the Service Provider in config/app.php:"
echo "      App\\Providers\\CartServiceProvider::class,"
echo ""
echo "   2. Register the routes in routes/api.php:"
echo "      require __DIR__.'/cart.php';"
echo ""
echo "   3. Run migrations:"
echo "      php artisan migrate"
echo ""
echo "   4. Seed the database:"
echo "      php artisan db:seed --class=CartSeeder"
echo ""
echo "   5. (Optional) Schedule cart cleanup in app/Console/Kernel.php:"
echo "      \$schedule->command('cart:clear-expired')->daily();"
echo ""
echo "📋 API Endpoints Created:"
echo "   GET    /api/v1/cart                       - Get or create cart"
echo "   GET    /api/v1/cart/{id}                  - Get cart by ID"
echo "   GET    /api/v1/cart/session/{sessionId}   - Get cart by session"
echo "   POST   /api/v1/cart/items                 - Add item to cart"
echo "   PUT    /api/v1/cart/items/{itemId}        - Update item quantity"
echo "   DELETE /api/v1/cart/items/{itemId}        - Remove item"
echo "   DELETE /api/v1/cart/{cartId}/clear        - Clear cart"
echo "   GET    /api/v1/cart/{cartId}/summary      - Get cart summary"
echo "   GET    /api/v1/cart/{cartId}/checkout-items - Get items for checkout"
echo "   POST   /api/v1/cart/{cartId}/extend       - Extend expiration"
echo "   POST   /api/v1/cart/merge                 - Merge guest cart (auth)"
echo ""
echo "🎉 Cart Module ready for Amazing Brindes!"
