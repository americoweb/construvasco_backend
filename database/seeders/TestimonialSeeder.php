<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Testimonial;
use App\Models\Product\Product;
use Illuminate\Support\Str;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        // Sample testimonials in Portuguese (Mozambique context)
        $testimonials = [
            [
                'product_slug' => 'cartoes-de-visita-350gsm-laminado',
                'testimonials' => [
                    [
                        'text' => 'Excelente qualidade dos cartões! A laminação ficou perfeita e o acabamento profissional. Meus clientes sempre elogiam quando recebem meu cartão de visita.',
                        'author' => 'Maria Santos',
                        'photo_url' => 'https://i.pravatar.cc/150?img=1',
                        'sort_order' => 1,
                    ],
                    [
                        'text' => 'Serviço rápido e cartões de alta qualidade. A impressão ficou nítida e as cores vibrantes. Recomendo para qualquer profissional que precise de cartões de visita.',
                        'author' => 'João Silva',
                        'photo_url' => 'https://i.pravatar.cc/150?img=2',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'product_slug' => 'flyers-170gsm',
                'testimonials' => [
                    [
                        'text' => 'Os flyers ficaram perfeitos para nossa promoção! A qualidade do papel e a impressão superaram nossas expectativas. Conseguiram entregar no prazo mesmo com urgência.',
                        'author' => 'Ana Costa',
                        'photo_url' => 'https://i.pravatar.cc/150?img=3',
                        'sort_order' => 1,
                    ],
                    [
                        'text' => 'Excelente custo-benefício. Os flyers são ideais para distribuição em eventos e a qualidade do material é muito boa. Voltarei a encomendar!',
                        'author' => 'Carlos Mendes',
                        'photo_url' => 'https://i.pravatar.cc/150?img=4',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'product_slug' => 'camisa-polo-bordada',
                'testimonials' => [
                    [
                        'text' => 'As camisas polo ficaram excelentes! O tecido é de alta qualidade e o bordado do logo ficou muito profissional. Nossa equipe adorou!',
                        'author' => 'Pedro Alves',
                        'photo_url' => 'https://i.pravatar.cc/150?img=5',
                        'sort_order' => 1,
                    ],
                    [
                        'text' => 'Perfeito para uniformes corporativos. A qualidade do algodão é superior e o bordado está impecável. Ótimo atendimento e entrega pontual.',
                        'author' => 'Sofia Ferreira',
                        'photo_url' => 'https://i.pravatar.cc/150?img=6',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'product_slug' => 'caneta-plastica',
                'testimonials' => [
                    [
                        'text' => 'As canetas personalizadas ficaram lindas! A impressão está nítida e a qualidade é excelente. Perfeitas para brindes corporativos.',
                        'author' => 'Luís Rodrigues',
                        'photo_url' => 'https://i.pravatar.cc/150?img=7',
                        'sort_order' => 1,
                    ],
                    [
                        'text' => 'Excelente qualidade e preço justo. As canetas são resistentes e a personalização ficou perfeita. Nossos clientes adoraram receber este brinde!',
                        'author' => 'Rita Oliveira',
                        'photo_url' => 'https://i.pravatar.cc/150?img=8',
                        'sort_order' => 2,
                    ],
                ],
            ],
            [
                'product_slug' => 'sacos-de-papel-kraft',
                'testimonials' => [
                    [
                        'text' => 'Sacos de papel Kraft de excelente qualidade! Nossos clientes valorizam muito a preocupação com o meio ambiente. A impressão ficou perfeita e resistente.',
                        'author' => 'Miguel Sousa',
                        'photo_url' => 'https://i.pravatar.cc/150?img=9',
                        'sort_order' => 1,
                    ],
                ],
            ],
        ];

        foreach ($testimonials as $productTestimonials) {
            $product = Product::where('slug', $productTestimonials['product_slug'])->first();
            
            if (!$product) {
                $this->command->warn("Product with slug '{$productTestimonials['product_slug']}' not found. Skipping testimonials.");
                continue;
            }

            foreach ($productTestimonials['testimonials'] as $testimonialData) {
                // Check if testimonial already exists for this product and author
                $existing = Testimonial::where('product_id', $product->id)
                    ->where('author', $testimonialData['author'])
                    ->first();

                if (!$existing) {
                    Testimonial::create([
                        'uuid' => Str::uuid(),
                        'product_id' => $product->id,
                        'text' => $testimonialData['text'],
                        'author' => $testimonialData['author'],
                        'photo_url' => $testimonialData['photo_url'],
                        'is_active' => true,
                        'sort_order' => $testimonialData['sort_order'],
                    ]);
                }
            }
        }

        $this->command->info('Testimonials seeded successfully!');
    }
}

