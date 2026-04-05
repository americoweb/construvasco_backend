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
