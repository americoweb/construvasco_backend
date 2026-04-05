<?php

namespace Database\Factories\Product;

use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductColorFactory extends Factory
{
    protected $model = ProductColor::class;

    public function definition(): array
    {
        $colors = [
            ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
            ['name' => 'Preto', 'hex_code' => '#000000'],
            ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
            ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
            ['name' => 'Cinza', 'hex_code' => '#808080'],
            ['name' => 'Prata', 'hex_code' => '#C0C0C0'],
            ['name' => 'Natural', 'hex_code' => '#F5F5DC'],
            ['name' => 'Kraft', 'hex_code' => '#C4A35A'],
        ];

        $color = $this->faker->randomElement($colors);

        return [
            'name' => $color['name'],
            'hex_code' => $color['hex_code'],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 10),
            'stock_quantity' => $this->faker->optional()->numberBetween(0, 1000),
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 0,
        ]);
    }
}
