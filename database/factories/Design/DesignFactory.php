<?php

namespace Database\Factories\Design;

use App\Models\Design\Design;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DesignFactory extends Factory
{
    protected $model = Design::class;

    public function definition(): array
    {
        $product = Product::inRandomOrder()->first();
        
        return [
            'uuid' => Str::uuid(),
            'user_id' => null,
            'session_id' => Str::random(32),
            'product_id' => $product?->id ?? 1,
            'product_color_id' => $product?->colors()->first()?->id ?? 1,
            'product_print_area_id' => $product?->printAreas()->first()?->id ?? 1,
            'prompt' => $this->faker->sentence(10),
            'mockup_url' => $this->faker->imageUrl(640, 480, 'design'),
            'mockup_base64' => null,
            'logo_path' => null,
            'logo_mime_type' => null,
            'reference_image_path' => null,
            'reference_mime_type' => null,
            'status' => DesignStatus::COMPLETED,
            'generation_attempts' => $this->faker->numberBetween(1, 3),
            'ai_model_used' => 'gemini-pro-vision',
            'ai_response_metadata' => null,
            'is_from_suggestion' => false,
            'suggestion_id' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::COMPLETED,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::DRAFT,
            'mockup_url' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => [
                'error' => 'Generation failed',
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function fromSuggestion(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_from_suggestion' => true,
            'suggestion_id' => Str::uuid(),
        ]);
    }

    public function withLogo(): static
    {
        return $this->state(fn (array $attributes) => [
            'logo_path' => 'logos/' . Str::random(20) . '.png',
            'logo_mime_type' => 'image/png',
        ]);
    }
}
