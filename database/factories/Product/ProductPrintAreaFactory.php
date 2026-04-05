<?php

namespace Database\Factories\Product;

use App\Models\Product\ProductPrintArea;
use App\Enums\Product\PrintAreaPosition;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPrintAreaFactory extends Factory
{
    protected $model = ProductPrintArea::class;

    public function definition(): array
    {
        $areas = [
            [
                'name' => 'Frente (Peito)',
                'position' => PrintAreaPosition::FRONT_CHEST,
                'max_width_cm' => 30,
                'max_height_cm' => 35,
            ],
            [
                'name' => 'Costas (Completo)',
                'position' => PrintAreaPosition::BACK_FULL,
                'max_width_cm' => 35,
                'max_height_cm' => 45,
            ],
            [
                'name' => 'Envolvente Completo',
                'position' => PrintAreaPosition::FULL_WRAP,
                'max_width_cm' => 25,
                'max_height_cm' => 10,
            ],
        ];

        $area = $this->faker->randomElement($areas);

        return [
            'name' => $area['name'],
            'position' => $area['position'],
            'description' => $this->faker->sentence(),
            'max_width_cm' => $area['max_width_cm'],
            'max_height_cm' => $area['max_height_cm'],
            'additional_price' => $this->faker->randomElement([0, 50, 100, 150]),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 5),
        ];
    }
}
