<?php

namespace Database\Factories\Product;

use App\Models\Product\Product;
use App\Enums\Product\ProductStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Camiseta',
            'Caneca',
            'Cartão de Visita',
            'Pôster',
            'Camisa Polo',
            'Caderno de Anotações',
            'Garrafa de Água',
            'Sacola Ecológica',
        ]);

        return [
            'uuid' => Str::uuid(),
            'name' => $name,
            'slug' => Str::slug($name) . '-' . $this->faker->unique()->numberBetween(1, 9999),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomElement([250, 350, 450, 600, 750, 800, 1000, 1500]),
            'min_quantity' => $this->faker->randomElement([1, 5, 10, 20, 25, 50]),
            'image_url' => $this->faker->imageUrl(640, 480, 'product'),
            'base_image_url' => $this->faker->imageUrl(640, 480, 'product'),
            'design_hint' => $this->faker->sentence(15),
            'status' => ProductStatus::ACTIVE,
            'is_featured' => $this->faker->boolean(30),
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProductStatus::INACTIVE,
        ]);
    }

    public function tshirt(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Camiseta',
            'slug' => 'camiseta-' . Str::random(6),
            'price' => 1000,
            'min_quantity' => 1,
            'design_hint' => 'Design funciona melhor centralizado no peito, evite perto do colarinho/mangas',
        ]);
    }

    public function mug(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Caneca',
            'slug' => 'caneca-' . Str::random(6),
            'price' => 600,
            'min_quantity' => 1,
            'design_hint' => 'Padrões panorâmicos ou repetitivos funcionam bem',
        ]);
    }
}
