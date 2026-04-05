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
