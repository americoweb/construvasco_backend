#!/bin/bash

# iOPS Amazing Brindes - Orders Module Setup Script
# Run this from the Laravel root directory (where vendor folder exists)

echo "🛒 Setting up Amazing Brindes Orders Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directory structure
echo "📁 Creating directory structure..."

mkdir -p app/Http/Controllers/Order
mkdir -p app/Services/Order
mkdir -p app/Repositories/Order/Contracts
mkdir -p app/Repositories/Order/Eloquent
mkdir -p app/Models/Order
mkdir -p app/Http/Requests/Order
mkdir -p app/Http/Resources/Order
mkdir -p app/Events/Order
mkdir -p app/Enums/Order
mkdir -p app/Jobs/Order
mkdir -p database/migrations
mkdir -p database/factories/Order
mkdir -p database/seeders

echo "✅ Directory structure created!"

# ============================================
# ENUMS
# ============================================
echo "🏷️ Creating Enums..."

cat > app/Enums/Order/OrderStatus.php << 'EOF'
<?php

namespace App\Enums\Order;

enum OrderStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PRODUCTION = 'in_production';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::CONFIRMED => 'Confirmado',
            self::IN_PRODUCTION => 'Em Produção',
            self::SHIPPED => 'Enviado',
            self::DELIVERED => 'Entregue',
            self::CANCELLED => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::CONFIRMED => 'blue',
            self::IN_PRODUCTION => 'orange',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
        };
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($newStatus, [self::IN_PRODUCTION, self::CANCELLED]),
            self::IN_PRODUCTION => in_array($newStatus, [self::SHIPPED, self::CANCELLED]),
            self::SHIPPED => in_array($newStatus, [self::DELIVERED]),
            self::DELIVERED => false,
            self::CANCELLED => false,
        };
    }
}
EOF

cat > app/Enums/Order/PaymentStatus.php << 'EOF'
<?php

namespace App\Enums\Order;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pendente',
            self::PAID => 'Pago',
            self::FAILED => 'Falhou',
            self::REFUNDED => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::PENDING => 'yellow',
            self::PAID => 'green',
            self::FAILED => 'red',
            self::REFUNDED => 'gray',
        };
    }
}
EOF

# ============================================
# MODELS
# ============================================
echo "📦 Creating Models..."

cat > app/Models/Order/Order.php << 'EOF'
<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use App\Traits\HasUuid;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes, HasUuid;

    protected $fillable = [
        'uuid',
        'order_number',
        'user_id',
        'session_id',
        'status',
        'payment_status',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'shipping_name',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'shipping_phone',
        'shipping_whatsapp',
        'billing_name',
        'billing_email',
        'notes',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            
            if (empty($order->currency)) {
                $order->currency = 'MT';
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = strtoupper(Str::random(9));
        } while (self::where('order_number', $number)->exists());
        
        return $number;
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    public function scopePending($query)
    {
        return $query->where('status', OrderStatus::PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [OrderStatus::CANCELLED, OrderStatus::DELIVERED]);
    }

    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === OrderStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    public function isDelivered(): bool
    {
        return $this->status === OrderStatus::DELIVERED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            OrderStatus::PENDING,
            OrderStatus::CONFIRMED,
            OrderStatus::IN_PRODUCTION,
        ]);
    }

    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    public function getFormattedTotalAttribute(): string
    {
        return number_format($this->total_amount, 2, ',', '.') . ' ' . $this->currency;
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(function ($item) {
            return $item->unit_price * $item->quantity;
        });
        
        $this->total_amount = $this->subtotal + $this->shipping_cost + $this->tax_amount - $this->discount_amount;
    }
}
EOF

cat > app/Models/Order/OrderItem.php << 'EOF'
<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Models\Design\Design;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'design_id',
        'product_color_id',
        'product_print_area_id',
        'product_name',
        'product_sku',
        'color_name',
        'color_hex_code',
        'print_area_name',
        'design_prompt',
        'mockup_url',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
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

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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
}
EOF

cat > app/Models/Order/OrderStatusHistory.php << 'EOF'
<?php

namespace App\Models\Order;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\Order\OrderStatus;

class OrderStatusHistory extends Model
{
    use HasFactory;

    protected $table = 'order_status_history';

    protected $fillable = [
        'order_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'previous_status' => OrderStatus::class,
        'new_status' => OrderStatus::class,
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
EOF

# ============================================
# REPOSITORY CONTRACTS
# ============================================
echo "📋 Creating Repository Contracts..."

cat > app/Repositories/Order/Contracts/OrderRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Order\Contracts;

use App\Models\Order\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    
    public function findByUuid(string $uuid): ?Order;
    
    public function findByOrderNumber(string $orderNumber): ?Order;
    
    public function create(array $data): Order;
    
    public function update(int $id, array $data): Order;
    
    public function delete(int $id): bool;
    
    public function getByUser(int $userId): Collection;
    
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    public function getBySession(string $sessionId): Collection;
    
    public function getWithItems(int $id): ?Order;
    
    public function getPending(): Collection;
    
    public function getActive(): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
EOF

cat > app/Repositories/Order/Contracts/OrderItemRepositoryInterface.php << 'EOF'
<?php

namespace App\Repositories\Order\Contracts;

use App\Models\Order\OrderItem;
use Illuminate\Database\Eloquent\Collection;

interface OrderItemRepositoryInterface
{
    public function findById(int $id): ?OrderItem;
    
    public function create(array $data): OrderItem;
    
    public function update(int $id, array $data): OrderItem;
    
    public function delete(int $id): bool;
    
    public function getByOrder(int $orderId): Collection;
    
    public function createMany(array $items): Collection;
}
EOF

# ============================================
# REPOSITORY IMPLEMENTATIONS
# ============================================
echo "🗃️ Creating Repository Implementations..."

cat > app/Repositories/Order/Eloquent/EloquentOrderRepository.php << 'EOF'
<?php

namespace App\Repositories\Order\Eloquent;

use App\Models\Order\Order;
use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        protected Order $model
    ) {}

    public function findById(int $id): ?Order
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Order
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Order
    {
        $order = $this->findById($id);
        $order->update($data);
        return $order->fresh();
    }

    public function delete(int $id): bool
    {
        $order = $this->findById($id);
        return $order->delete();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->byUser($userId)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->byUser($userId)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->model->bySession($sessionId)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getWithItems(int $id): ?Order
    {
        return $this->model->with(['items', 'statusHistory'])->find($id);
    }

    public function getPending(): Collection
    {
        return $this->model->pending()
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getActive(): Collection
    {
        return $this->model->active()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('items');
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhere('shipping_name', 'like', "%{$filters['search']}%")
                  ->orWhere('billing_email', 'like', "%{$filters['search']}%");
            });
        }
    }
}
EOF

cat > app/Repositories/Order/Eloquent/EloquentOrderItemRepository.php << 'EOF'
<?php

namespace App\Repositories\Order\Eloquent;

use App\Models\Order\OrderItem;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderItemRepository implements OrderItemRepositoryInterface
{
    public function __construct(
        protected OrderItem $model
    ) {}

    public function findById(int $id): ?OrderItem
    {
        return $this->model->find($id);
    }

    public function create(array $data): OrderItem
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): OrderItem
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

    public function getByOrder(int $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)->get();
    }

    public function createMany(array $items): Collection
    {
        $created = collect();
        
        foreach ($items as $itemData) {
            $created->push($this->create($itemData));
        }
        
        return $created;
    }
}
EOF

# ============================================
# SERVICES
# ============================================
echo "⚙️ Creating Services..."

cat > app/Services/Order/OrderService.php << 'EOF'
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
            
            // Create order
            $order = $this->orderRepository->create($orderData);
            
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
        return $this->updateStatus($id, OrderStatus::CONFIRMED, 'Pedido confirmado');
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
        return $this->orderRepository->update($id, [
            'payment_status' => $paymentStatus
        ]);
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
}
EOF

# ============================================
# HTTP REQUESTS
# ============================================
echo "✅ Creating Form Requests..."

cat > app/Http/Requests/Order/CreateOrderRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'session_id' => 'nullable|string|max:255',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_phone' => 'nullable|string|max:20',
            'shipping_whatsapp' => 'required|string|max:20',
            'billing_name' => 'nullable|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
            'shipping_cost' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            
            // Order items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.design_id' => 'nullable|exists:designs,id',
            'items.*.product_color_id' => 'required|exists:product_colors,id',
            'items.*.product_print_area_id' => 'required|exists:product_print_areas,id',
            'items.*.product_name' => 'required|string|max:255',
            'items.*.color_name' => 'required|string|max:100',
            'items.*.color_hex_code' => 'required|string|max:7',
            'items.*.print_area_name' => 'required|string|max:100',
            'items.*.design_prompt' => 'nullable|string|max:2000',
            'items.*.mockup_url' => 'nullable|string|max:1000',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_name.required' => 'O nome de envio é obrigatório',
            'shipping_address.required' => 'O endereço de envio é obrigatório',
            'shipping_whatsapp.required' => 'O WhatsApp é obrigatório',
            'items.required' => 'O pedido deve conter pelo menos um item',
            'items.min' => 'O pedido deve conter pelo menos um item',
            'items.*.product_id.required' => 'O produto é obrigatório',
            'items.*.quantity.required' => 'A quantidade é obrigatória',
            'items.*.quantity.min' => 'A quantidade mínima é 1',
            'items.*.unit_price.required' => 'O preço unitário é obrigatório',
        ];
    }
}
EOF

cat > app/Http/Requests/Order/UpdateOrderRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_name' => 'sometimes|string|max:255',
            'shipping_address' => 'sometimes|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'nullable|string|max:100',
            'shipping_phone' => 'nullable|string|max:20',
            'shipping_whatsapp' => 'sometimes|string|max:20',
            'billing_name' => 'nullable|string|max:255',
            'billing_email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
EOF

cat > app/Http/Requests/Order/UpdateOrderStatusRequest.php << 'EOF'
<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\Order\OrderStatus;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório',
        ];
    }
}
EOF

# ============================================
# HTTP RESOURCES
# ============================================
echo "📤 Creating API Resources..."

cat > app/Http/Resources/Order/OrderResource.php << 'EOF'
<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'user_id' => $this->user_id,
            'session_id' => $this->session_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'payment_status_color' => $this->payment_status->color(),
            'subtotal' => (float) $this->subtotal,
            'shipping_cost' => (float) $this->shipping_cost,
            'tax_amount' => (float) $this->tax_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'total_items' => $this->total_items,
            'shipping' => [
                'name' => $this->shipping_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'state' => $this->shipping_state,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
                'phone' => $this->shipping_phone,
                'whatsapp' => $this->shipping_whatsapp,
            ],
            'billing' => [
                'name' => $this->billing_name,
                'email' => $this->billing_email,
            ],
            'notes' => $this->notes,
            'can_be_cancelled' => $this->canBeCancelled(),
            'is_pending' => $this->isPending(),
            'is_delivered' => $this->isDelivered(),
            'is_cancelled' => $this->isCancelled(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'status_history' => OrderStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Order/OrderListResource.php << 'EOF'
<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'payment_status' => $this->payment_status->value,
            'payment_status_label' => $this->payment_status->label(),
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'total_items' => $this->total_items,
            'shipping_name' => $this->shipping_name,
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Order/OrderItemResource.php << 'EOF'
<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'design_id' => $this->design_id,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku,
            'color_name' => $this->color_name,
            'color_hex_code' => $this->color_hex_code,
            'print_area_name' => $this->print_area_name,
            'design_prompt' => $this->design_prompt,
            'mockup_url' => $this->mockup_url,
            'quantity' => $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'formatted_unit_price' => $this->formatted_unit_price,
            'total_price' => (float) $this->total_price,
            'formatted_total' => $this->formatted_total,
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

cat > app/Http/Resources/Order/OrderStatusHistoryResource.php << 'EOF'
<?php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'previous_status' => $this->previous_status?->value,
            'previous_status_label' => $this->previous_status?->label(),
            'new_status' => $this->new_status->value,
            'new_status_label' => $this->new_status->label(),
            'notes' => $this->notes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
EOF

# ============================================
# CONTROLLERS
# ============================================
echo "🎮 Creating Controllers..."

cat > app/Http/Controllers/Order/OrderController.php << 'EOF'
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
        $order->load(['items', 'statusHistory']);

        return response()->json([
            'data' => new OrderResource($order)
        ]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $order = $this->orderService->findByUuid($uuid);
        $order->load(['items', 'statusHistory']);

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

    public function markInProduction(int $id): JsonResponse
    {
        $order = $this->orderService->markInProduction($id);

        return response()->json([
            'data' => new OrderResource($order),
            'message' => 'Pedido marcado como em produção'
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
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔌 Creating Service Provider..."

cat > app/Providers/OrderServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use App\Repositories\Order\Eloquent\EloquentOrderRepository;
use App\Repositories\Order\Eloquent\EloquentOrderItemRepository;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            OrderRepositoryInterface::class,
            EloquentOrderRepository::class
        );

        $this->app->bind(
            OrderItemRepositoryInterface::class,
            EloquentOrderItemRepository::class
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

cat > routes/order.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Order\OrderController;

/*
|--------------------------------------------------------------------------
| Order API Routes
|--------------------------------------------------------------------------
|
| Routes for Amazing Brindes Order Module
|
*/

Route::prefix('v1')->group(function () {
    
    Route::prefix('orders')->group(function () {
        // Create order
        Route::post('/', [OrderController::class, 'store']);
        
        // Get order by ID
        Route::get('/{id}', [OrderController::class, 'show'])->where('id', '[0-9]+');
        
        // Get order by order number
        Route::get('/number/{orderNumber}', [OrderController::class, 'showByOrderNumber']);
        
        // Get order by UUID
        Route::get('/uuid/{uuid}', [OrderController::class, 'showByUuid']);
        
        // Update order
        Route::put('/{id}', [OrderController::class, 'update']);
        
        // Update shipping address
        Route::patch('/{id}/shipping-address', [OrderController::class, 'updateShippingAddress']);
        
        // Cancel order
        Route::post('/{id}/cancel', [OrderController::class, 'cancel']);
        
        // Get orders by session
        Route::get('/session/{sessionId}', [OrderController::class, 'getBySession']);
    });

    // Admin order routes
    Route::prefix('admin/orders')->group(function () {
        // List all orders
        Route::get('/', [OrderController::class, 'index']);
        
        // Get pending orders
        Route::get('/pending', [OrderController::class, 'getPending']);
        
        // Get active orders
        Route::get('/active', [OrderController::class, 'getActive']);
        
        // Update order status
        Route::patch('/{id}/status', [OrderController::class, 'updateStatus']);
        
        // Quick status updates
        Route::post('/{id}/confirm', [OrderController::class, 'confirm']);
        Route::post('/{id}/in-production', [OrderController::class, 'markInProduction']);
        Route::post('/{id}/ship', [OrderController::class, 'markShipped']);
        Route::post('/{id}/deliver', [OrderController::class, 'markDelivered']);
        Route::post('/{id}/paid', [OrderController::class, 'markAsPaid']);
    });

    // Authenticated user orders
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user/orders', [OrderController::class, 'getByUser']);
    });
});
EOF

# ============================================
# DATABASE MIGRATIONS
# ============================================
echo "🗄️ Creating Database Migrations..."

CURRENT_TIME=$(date +%s)
TIMESTAMP_1=$(date -d "@$((CURRENT_TIME + 20))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 20)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_2=$(date -d "@$((CURRENT_TIME + 21))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 21)) +%Y_%m_%d_%H%M%S)
TIMESTAMP_3=$(date -d "@$((CURRENT_TIME + 22))" +%Y_%m_%d_%H%M%S 2>/dev/null || date -r $((CURRENT_TIME + 22)) +%Y_%m_%d_%H%M%S)

cat > database/migrations/${TIMESTAMP_1}_create_orders_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number', 20)->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('pending');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 10)->default('MT');
            $table->string('shipping_name');
            $table->text('shipping_address');
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state')->nullable();
            $table->string('shipping_postal_code', 20)->nullable();
            $table->string('shipping_country')->nullable();
            $table->string('shipping_phone', 20)->nullable();
            $table->string('shipping_whatsapp', 20);
            $table->string('billing_name')->nullable();
            $table->string('billing_email')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['session_id', 'status']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('order_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_2}_create_order_items_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('design_id')->nullable();
            $table->unsignedBigInteger('product_color_id');
            $table->unsignedBigInteger('product_print_area_id');
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->string('color_name');
            $table->string('color_hex_code', 7);
            $table->string('print_area_name');
            $table->text('design_prompt')->nullable();
            $table->text('mockup_url')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('total_price', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('order_id');
            $table->index('product_id');
            $table->index('design_id');

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')
                  ->on('products')
                  ->onDelete('restrict');

            $table->foreign('design_id')
                  ->references('id')
                  ->on('designs')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
EOF

cat > database/migrations/${TIMESTAMP_3}_create_order_status_history_table.php << 'EOF'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->string('changed_by')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);

            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
EOF

# ============================================
# FACTORIES
# ============================================
echo "🏭 Creating Factories..."

cat > database/factories/Order/OrderFactory.php << 'EOF'
<?php

namespace Database\Factories\Order;

use App\Models\Order\Order;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 500, 10000);
        $shipping = $this->faker->randomElement([0, 150, 250, 500]);
        $discount = $this->faker->randomElement([0, 0, 0, 100, 200]);
        
        return [
            'uuid' => Str::uuid(),
            'order_number' => strtoupper(Str::random(9)),
            'user_id' => null,
            'session_id' => Str::random(32),
            'status' => OrderStatus::PENDING,
            'payment_status' => PaymentStatus::PENDING,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax_amount' => 0,
            'discount_amount' => $discount,
            'total_amount' => $subtotal + $shipping - $discount,
            'currency' => 'MT',
            'shipping_name' => $this->faker->name(),
            'shipping_address' => $this->faker->address(),
            'shipping_city' => 'Maputo',
            'shipping_state' => 'Maputo',
            'shipping_postal_code' => $this->faker->postcode(),
            'shipping_country' => 'Moçambique',
            'shipping_phone' => '+258' . $this->faker->numerify('8#########'),
            'shipping_whatsapp' => '+258' . $this->faker->numerify('8#########'),
            'billing_name' => $this->faker->name(),
            'billing_email' => $this->faker->email(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    public function inProduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::IN_PRODUCTION,
            'confirmed_at' => now()->subDays(2),
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::SHIPPED,
            'confirmed_at' => now()->subDays(5),
            'shipped_at' => now()->subDays(1),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::DELIVERED,
            'confirmed_at' => now()->subDays(10),
            'shipped_at' => now()->subDays(5),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelado pelo cliente',
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::PAID,
        ]);
    }
}
EOF

cat > database/factories/Order/OrderItemFactory.php << 'EOF'
<?php

namespace Database\Factories\Order;

use App\Models\Order\OrderItem;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first();
        $quantity = $this->faker->numberBetween(1, 50);
        $unitPrice = $product?->price ?? $this->faker->randomElement([250, 350, 450, 600, 750, 800, 1000, 1500]);
        
        return [
            'product_id' => $product?->id ?? 1,
            'design_id' => null,
            'product_color_id' => $product?->colors()->first()?->id ?? 1,
            'product_print_area_id' => $product?->printAreas()->first()?->id ?? 1,
            'product_name' => $product?->name ?? 'Camiseta',
            'product_sku' => 'SKU-' . strtoupper(Str::random(8)),
            'color_name' => 'Branco',
            'color_hex_code' => '#FFFFFF',
            'print_area_name' => 'Frente (Peito)',
            'design_prompt' => $this->faker->sentence(10),
            'mockup_url' => $this->faker->imageUrl(640, 480, 'product'),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $unitPrice * $quantity,
        ];
    }
}
EOF

# ============================================
# SEEDERS
# ============================================
echo "🌱 Creating Seeders..."

cat > database/seeders/OrderSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order\Order;
use App\Models\Order\OrderItem;
use App\Models\Order\OrderStatusHistory;
use App\Models\Product\Product;
use App\Enums\Order\OrderStatus;
use App\Enums\Order\PaymentStatus;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with(['colors', 'printAreas'])->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        // Create pending orders
        $this->createOrders($products, 5, OrderStatus::PENDING);
        
        // Create confirmed orders
        $this->createOrders($products, 3, OrderStatus::CONFIRMED);
        
        // Create in production orders
        $this->createOrders($products, 4, OrderStatus::IN_PRODUCTION);
        
        // Create shipped orders
        $this->createOrders($products, 2, OrderStatus::SHIPPED);
        
        // Create delivered orders
        $this->createOrders($products, 8, OrderStatus::DELIVERED);
        
        // Create cancelled orders
        $this->createOrders($products, 2, OrderStatus::CANCELLED);
    }

    private function createOrders($products, int $count, OrderStatus $status): void
    {
        for ($i = 0; $i < $count; $i++) {
            $items = [];
            $subtotal = 0;
            $numItems = rand(1, 4);
            
            for ($j = 0; $j < $numItems; $j++) {
                $product = $products->random();
                
                if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                    continue;
                }
                
                $color = $product->colors->random();
                $printArea = $product->printAreas->random();
                $quantity = rand($product->min_quantity, $product->min_quantity * 3);
                $unitPrice = $product->price;
                $total = $unitPrice * $quantity;
                $subtotal += $total;
                
                $items[] = [
                    'product_id' => $product->id,
                    'product_color_id' => $color->id,
                    'product_print_area_id' => $printArea->id,
                    'product_name' => $product->name,
                    'product_sku' => 'SKU-' . strtoupper(Str::random(8)),
                    'color_name' => $color->name,
                    'color_hex_code' => $color->hex_code,
                    'print_area_name' => $printArea->name,
                    'design_prompt' => 'Design personalizado para ' . $product->name,
                    'mockup_url' => 'https://via.placeholder.com/640x480/cccccc/666666?text=' . urlencode($product->name),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $total,
                ];
            }
            
            if (empty($items)) {
                continue;
            }
            
            $shipping = fake()->randomElement([0, 150, 250, 500]);
            $discount = fake()->randomElement([0, 0, 100, 200]);
            
            $orderData = [
                'uuid' => Str::uuid(),
                'order_number' => strtoupper(Str::random(9)),
                'session_id' => 'seed_' . Str::random(20),
                'status' => $status,
                'payment_status' => in_array($status, [OrderStatus::IN_PRODUCTION, OrderStatus::SHIPPED, OrderStatus::DELIVERED]) 
                    ? PaymentStatus::PAID 
                    : PaymentStatus::PENDING,
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'tax_amount' => 0,
                'discount_amount' => $discount,
                'total_amount' => $subtotal + $shipping - $discount,
                'currency' => 'MT',
                'shipping_name' => fake()->name(),
                'shipping_address' => fake()->address(),
                'shipping_city' => 'Maputo',
                'shipping_state' => 'Maputo',
                'shipping_postal_code' => fake()->postcode(),
                'shipping_country' => 'Moçambique',
                'shipping_phone' => '+258' . fake()->numerify('8#########'),
                'shipping_whatsapp' => '+258' . fake()->numerify('8#########'),
                'billing_name' => fake()->name(),
                'billing_email' => fake()->email(),
            ];
            
            // Set timestamps based on status
            if ($status !== OrderStatus::PENDING) {
                $orderData['confirmed_at'] = now()->subDays(rand(5, 15));
            }
            
            if (in_array($status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
                $orderData['shipped_at'] = now()->subDays(rand(1, 4));
            }
            
            if ($status === OrderStatus::DELIVERED) {
                $orderData['delivered_at'] = now()->subDays(rand(0, 1));
            }
            
            if ($status === OrderStatus::CANCELLED) {
                $orderData['cancelled_at'] = now()->subDays(rand(1, 10));
                $orderData['cancellation_reason'] = 'Cancelado pelo cliente';
            }
            
            $order = Order::create($orderData);
            
            foreach ($items as $itemData) {
                $itemData['order_id'] = $order->id;
                OrderItem::create($itemData);
            }
            
            // Create status history
            $this->createStatusHistory($order, $status);
        }
    }

    private function createStatusHistory(Order $order, OrderStatus $currentStatus): void
    {
        $statuses = [OrderStatus::PENDING];
        
        if ($currentStatus !== OrderStatus::PENDING) {
            $statuses[] = OrderStatus::CONFIRMED;
        }
        
        if (in_array($currentStatus, [OrderStatus::IN_PRODUCTION, OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
            $statuses[] = OrderStatus::IN_PRODUCTION;
        }
        
        if (in_array($currentStatus, [OrderStatus::SHIPPED, OrderStatus::DELIVERED])) {
            $statuses[] = OrderStatus::SHIPPED;
        }
        
        if ($currentStatus === OrderStatus::DELIVERED) {
            $statuses[] = OrderStatus::DELIVERED;
        }
        
        if ($currentStatus === OrderStatus::CANCELLED) {
            $statuses[] = OrderStatus::CANCELLED;
        }
        
        $previousStatus = null;
        foreach ($statuses as $index => $status) {
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => $status,
                'notes' => $status->label(),
                'metadata' => [
                    'timestamp' => now()->subDays(count($statuses) - $index)->toIso8601String(),
                ],
            ]);
            $previousStatus = $status;
        }
    }
}
EOF

cat > database/seeders/OrderModuleSeeder.php << 'EOF'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrderModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrderSeeder::class,
        ]);
    }
}
EOF

# ============================================
# FINAL OUTPUT
# ============================================
echo ""
echo "✅ Amazing Brindes Orders Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 2 Enums (OrderStatus, PaymentStatus)"
echo "   - 3 Models (Order, OrderItem, OrderStatusHistory)"
echo "   - 2 Repository Contracts"
echo "   - 2 Repository Implementations"
echo "   - 1 Service"
echo "   - 3 Form Requests"
echo "   - 4 API Resources"
echo "   - 1 Controller"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 3 Migrations"
echo "   - 2 Factories"
echo "   - 2 Seeders"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Register the Service Provider in config/app.php:"
echo "      App\\Providers\\OrderServiceProvider::class,"
echo ""
echo "   2. Register the routes in routes/api.php:"
echo "      require __DIR__.'/order.php';"
echo ""
echo "   3. Run migrations:"
echo "      php artisan migrate"
echo ""
echo "   4. Seed the database:"
echo "      php artisan db:seed --class=OrderSeeder"
echo ""
echo "📋 API Endpoints Created:"
echo "   POST   /api/v1/orders                     - Create order"
echo "   GET    /api/v1/orders/{id}                - Get order details"
echo "   GET    /api/v1/orders/number/{number}     - Get by order number"
echo "   GET    /api/v1/orders/uuid/{uuid}         - Get by UUID"
echo "   PUT    /api/v1/orders/{id}                - Update order"
echo "   POST   /api/v1/orders/{id}/cancel         - Cancel order"
echo "   GET    /api/v1/orders/session/{id}        - Get by session"
echo ""
echo "   Admin endpoints:"
echo "   GET    /api/v1/admin/orders               - List all orders"
echo "   GET    /api/v1/admin/orders/pending       - Pending orders"
echo "   GET    /api/v1/admin/orders/active        - Active orders"
echo "   POST   /api/v1/admin/orders/{id}/confirm  - Confirm order"
echo "   POST   /api/v1/admin/orders/{id}/ship     - Mark as shipped"
echo "   POST   /api/v1/admin/orders/{id}/deliver  - Mark as delivered"
echo "   POST   /api/v1/admin/orders/{id}/paid     - Mark as paid"
echo ""
echo "🎉 Orders Module ready for Amazing Brindes!"
