<?php

namespace Database\Seeders;

use App\Models\Construction\ConstructionService;
use App\Models\Construction\ServiceCategory;
use Illuminate\Database\Seeder;

class ConstructionProjectSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Residencial',
                'slug' => 'residencial',
                'description' => 'Projetos de moradia, reabilitação e expansão residencial.',
                'services' => [
                    ['name' => 'Casa T1 Essencial', 'slug' => 'casa-t1-essencial', 'base_price' => 55000],
                    ['name' => 'Casa T2 Compacta', 'slug' => 'casa-t2-compacta', 'base_price' => 78000],
                    ['name' => 'Casa T3 Familiar', 'slug' => 'casa-t3-familiar', 'base_price' => 108000],
                    ['name' => 'Casa T4 Premium', 'slug' => 'casa-t4-premium', 'base_price' => 158000],
                ],
            ],
            [
                'name' => 'Comercial e Serviços',
                'slug' => 'comercial-servicos',
                'description' => 'Projetos para comércio, hotelaria, saúde e educação.',
                'services' => [
                    ['name' => 'Centro Comercial de Bairro', 'slug' => 'centro-comercial-bairro', 'base_price' => 510000],
                    ['name' => 'Edifício Corporativo', 'slug' => 'edificio-corporativo', 'base_price' => 1200000],
                ],
            ],
            [
                'name' => 'Infraestrutura',
                'slug' => 'infraestrutura-mobilidade',
                'description' => 'Vias, drenagem e obras públicas.',
                'services' => [
                    ['name' => 'Estrada Rural de Ligação', 'slug' => 'estrada-rural-ligacao', 'base_price' => 870000],
                    ['name' => 'Sistema de Drenagem Pluvial', 'slug' => 'sistema-drenagem-pluvial', 'base_price' => 410000],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $category = ServiceCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'is_active' => true,
                ]
            );

            foreach ($categoryData['services'] as $service) {
                ConstructionService::updateOrCreate(
                    ['slug' => $service['slug']],
                    [
                        'service_category_id' => $category->id,
                        'name' => $service['name'],
                        'description' => 'Serviço de projecto arquitectónico com briefing e entregáveis técnicos.',
                        'base_price' => $service['base_price'],
                        'currency' => 'MZN',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
