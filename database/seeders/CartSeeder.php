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
