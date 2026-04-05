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
