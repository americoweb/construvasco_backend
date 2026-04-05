<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Design\Design;
use App\Models\Design\DesignRefinement;
use App\Models\Product\Product;
use App\Enums\Design\DesignStatus;
use Illuminate\Support\Str;

class DesignSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::with(['colors', 'printAreas'])->get();
        
        if ($products->isEmpty()) {
            $this->command->warn('No products found. Please run ProductSeeder first.');
            return;
        }

        $prompts = [
            'Logo minimalista com cores vibrantes para evento corporativo',
            'Design moderno com tipografia bold para lançamento de produto',
            'Arte abstrata com formas geométricas em azul e branco',
            'Ilustração de mascote divertido para equipe de vendas',
            'Pattern repetitivo com ícones de tecnologia',
            'Design elegante com bordas douradas para evento VIP',
            'Arte tropical com folhas e flores para festa de verão',
            'Logo esportivo com efeito 3D para time de futebol',
            'Design vintage com texturas de papel antigo',
            'Padrão floral delicado para produtos femininos',
        ];

        foreach ($products as $product) {
            if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                continue;
            }

            // Create 3-5 designs per product
            $numDesigns = rand(3, 5);
            
            for ($i = 0; $i < $numDesigns; $i++) {
                $design = Design::create([
                    'uuid' => Str::uuid(),
                    'user_id' => null,
                    'session_id' => 'seed_' . Str::random(20),
                    'product_id' => $product->id,
                    'product_color_id' => $product->colors->random()->id,
                    'product_print_area_id' => $product->printAreas->random()->id,
                    'prompt' => $prompts[array_rand($prompts)],
                    'mockup_url' => 'https://via.placeholder.com/640x480/cccccc/666666?text=' . urlencode($product->name),
                    'status' => DesignStatus::COMPLETED,
                    'generation_attempts' => rand(1, 3),
                    'ai_model_used' => 'gemini-pro-vision',
                    'is_from_suggestion' => (bool)rand(0, 1),
                    'suggestion_id' => rand(0, 1) ? Str::uuid() : null,
                ]);

                // Add refinements to some designs
                if (rand(0, 1)) {
                    $numRefinements = rand(1, 3);
                    
                    for ($j = 0; $j < $numRefinements; $j++) {
                        DesignRefinement::create([
                            'design_id' => $design->id,
                            'refinement_prompt' => 'Ajuste: ' . $prompts[array_rand($prompts)],
                            'previous_mockup_url' => $design->mockup_url,
                            'new_mockup_url' => 'https://via.placeholder.com/640x480/aaaaaa/444444?text=Refinement+' . ($j + 1),
                            'status' => DesignStatus::COMPLETED,
                        ]);
                    }

                    $design->update(['status' => DesignStatus::REFINED]);
                }
            }
        }

        // Create some failed designs
        for ($i = 0; $i < 3; $i++) {
            $product = $products->random();
            
            if ($product->colors->isEmpty() || $product->printAreas->isEmpty()) {
                continue;
            }

            Design::create([
                'uuid' => Str::uuid(),
                'user_id' => null,
                'session_id' => 'seed_failed_' . Str::random(10),
                'product_id' => $product->id,
                'product_color_id' => $product->colors->first()->id,
                'product_print_area_id' => $product->printAreas->first()->id,
                'prompt' => 'Design que falhou na geração',
                'mockup_url' => null,
                'status' => DesignStatus::FAILED,
                'generation_attempts' => 3,
                'ai_model_used' => 'gemini-pro-vision',
                'ai_response_metadata' => [
                    'error' => 'Generation failed after 3 attempts',
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        }
    }
}
