<?php

namespace Database\Factories\Design;

use App\Models\Design\DesignRefinement;
use App\Models\Design\Design;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class DesignRefinementFactory extends Factory
{
    protected $model = DesignRefinement::class;

    public function definition(): array
    {
        return [
            'design_id' => Design::factory(),
            'refinement_prompt' => $this->faker->sentence(8),
            'previous_mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'new_mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'new_mockup_base64' => null,
            'status' => DesignStatus::COMPLETED,
            'ai_response_metadata' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::COMPLETED,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::FAILED,
            'new_mockup_url' => null,
        ]);
    }
}
