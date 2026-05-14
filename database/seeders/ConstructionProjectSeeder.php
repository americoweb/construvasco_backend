<?php

namespace Database\Seeders;

use App\Enums\Product\ProductStatus;
use App\Models\Product\Category;
use App\Models\Product\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ConstructionProjectSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Residencial',
                'slug' => 'residencial',
                'description' => 'Projetos de moradia, reabilitacao e expansao residencial.',
                'sort_order' => 1,
            ],
            [
                'name' => 'Comercial e Servicos',
                'slug' => 'comercial-servicos',
                'description' => 'Projetos para comercio, hotelaria, saude, educacao e servicos.',
                'sort_order' => 2,
            ],
            [
                'name' => 'Industrial e Logistica',
                'slug' => 'industrial-logistica',
                'description' => 'Projetos industriais, armazenagem, energia e suporte logistico.',
                'sort_order' => 3,
            ],
            [
                'name' => 'Infraestrutura e Mobilidade',
                'slug' => 'infraestrutura-mobilidade',
                'description' => 'Projetos de vias, drenagem, estruturas urbanas e obras publicas.',
                'sort_order' => 4,
            ],
            [
                'name' => 'Urbanismo e Espaco Publico',
                'slug' => 'urbanismo-espaco-publico',
                'description' => 'Projetos de espaco urbano, lazer, paisagismo e equipamentos publicos.',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::updateOrCreate(
                ['slug' => $categoryData['slug']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                    'sort_order' => $categoryData['sort_order'],
                    'is_active' => true,
                ]
            );
        }

        $projects = [
            ['name' => 'Casa T1 Essencial', 'slug' => 'casa-t1-essencial', 'category' => 'residencial', 'price' => 55000, 'featured' => true],
            ['name' => 'Casa T2 Compacta', 'slug' => 'casa-t2-compacta', 'category' => 'residencial', 'price' => 78000, 'featured' => true],
            ['name' => 'Casa T3 Familiar', 'slug' => 'casa-t3-familiar', 'category' => 'residencial', 'price' => 108000, 'featured' => true],
            ['name' => 'Casa T4 Premium', 'slug' => 'casa-t4-premium', 'category' => 'residencial', 'price' => 158000, 'featured' => true],
            ['name' => 'Moradia Duplex Moderna', 'slug' => 'moradia-duplex-moderna', 'category' => 'residencial', 'price' => 189000, 'featured' => false],
            ['name' => 'Moradia Geminada', 'slug' => 'moradia-geminada', 'category' => 'residencial', 'price' => 132000, 'featured' => false],
            ['name' => 'Predio Residencial Multifamiliar', 'slug' => 'predio-residencial-multifamiliar', 'category' => 'residencial', 'price' => 420000, 'featured' => false],
            ['name' => 'Condominio Fechado Residencial', 'slug' => 'condominio-fechado-residencial', 'category' => 'residencial', 'price' => 650000, 'featured' => false],
            ['name' => 'Remodelacao Integral de Casa', 'slug' => 'remodelacao-integral-casa', 'category' => 'residencial', 'price' => 98000, 'featured' => false],
            ['name' => 'Ampliacao de Moradia', 'slug' => 'ampliacao-de-moradia', 'category' => 'residencial', 'price' => 72000, 'featured' => false],

            ['name' => 'Centro Comercial de Bairro', 'slug' => 'centro-comercial-bairro', 'category' => 'comercial-servicos', 'price' => 510000, 'featured' => true],
            ['name' => 'Shopping Center Regional', 'slug' => 'shopping-center-regional', 'category' => 'comercial-servicos', 'price' => 1900000, 'featured' => true],
            ['name' => 'Loja de Rua Premium', 'slug' => 'loja-rua-premium', 'category' => 'comercial-servicos', 'price' => 210000, 'featured' => false],
            ['name' => 'Edificio Corporativo', 'slug' => 'edificio-corporativo', 'category' => 'comercial-servicos', 'price' => 1200000, 'featured' => false],
            ['name' => 'Hotel Urbano', 'slug' => 'hotel-urbano', 'category' => 'comercial-servicos', 'price' => 1500000, 'featured' => false],
            ['name' => 'Restaurante Tematico', 'slug' => 'restaurante-tematico', 'category' => 'comercial-servicos', 'price' => 280000, 'featured' => false],
            ['name' => 'Clinica Medica Privada', 'slug' => 'clinica-medica-privada', 'category' => 'comercial-servicos', 'price' => 620000, 'featured' => false],
            ['name' => 'Centro de Saude Comunitario', 'slug' => 'centro-saude-comunitario', 'category' => 'comercial-servicos', 'price' => 480000, 'featured' => false],
            ['name' => 'Escola Primaria Moderna', 'slug' => 'escola-primaria-moderna', 'category' => 'comercial-servicos', 'price' => 690000, 'featured' => false],
            ['name' => 'Creche e Jardim Infantil', 'slug' => 'creche-jardim-infantil', 'category' => 'comercial-servicos', 'price' => 350000, 'featured' => false],

            ['name' => 'Armazem Logistico', 'slug' => 'armazem-logistico', 'category' => 'industrial-logistica', 'price' => 860000, 'featured' => false],
            ['name' => 'Parque Industrial Leve', 'slug' => 'parque-industrial-leve', 'category' => 'industrial-logistica', 'price' => 2100000, 'featured' => false],
            ['name' => 'Unidade de Processamento Alimentar', 'slug' => 'unidade-processamento-alimentar', 'category' => 'industrial-logistica', 'price' => 930000, 'featured' => false],
            ['name' => 'Centro de Distribuicao', 'slug' => 'centro-distribuicao', 'category' => 'industrial-logistica', 'price' => 980000, 'featured' => false],
            ['name' => 'Oficina e Hangar Tecnico', 'slug' => 'oficina-hangar-tecnico', 'category' => 'industrial-logistica', 'price' => 540000, 'featured' => false],
            ['name' => 'Parque de Tanques e Bombagem', 'slug' => 'parque-tanques-bombagem', 'category' => 'industrial-logistica', 'price' => 1150000, 'featured' => false],
            ['name' => 'Planta Solar com Apoio Civil', 'slug' => 'planta-solar-apoio-civil', 'category' => 'industrial-logistica', 'price' => 1760000, 'featured' => false],

            ['name' => 'Estacionamento em Superficie', 'slug' => 'estacionamento-superficie', 'category' => 'infraestrutura-mobilidade', 'price' => 240000, 'featured' => true],
            ['name' => 'Parque de Estacionamento Estruturado', 'slug' => 'parque-estacionamento-estruturado', 'category' => 'infraestrutura-mobilidade', 'price' => 980000, 'featured' => false],
            ['name' => 'Estrada Rural de Ligacao', 'slug' => 'estrada-rural-ligacao', 'category' => 'infraestrutura-mobilidade', 'price' => 870000, 'featured' => true],
            ['name' => 'Via Urbana Requalificada', 'slug' => 'via-urbana-requalificada', 'category' => 'infraestrutura-mobilidade', 'price' => 740000, 'featured' => false],
            ['name' => 'Ponte de Pequeno Vao', 'slug' => 'ponte-pequeno-vao', 'category' => 'infraestrutura-mobilidade', 'price' => 1120000, 'featured' => false],
            ['name' => 'Sistema de Drenagem Pluvial', 'slug' => 'sistema-drenagem-pluvial', 'category' => 'infraestrutura-mobilidade', 'price' => 410000, 'featured' => false],
            ['name' => 'Pavimentacao com Bloco Intertravado', 'slug' => 'pavimentacao-bloco-intertravado', 'category' => 'infraestrutura-mobilidade', 'price' => 360000, 'featured' => false],
            ['name' => 'Calcadas e Acessibilidade', 'slug' => 'calcadas-acessibilidade', 'category' => 'infraestrutura-mobilidade', 'price' => 195000, 'featured' => false],

            ['name' => 'Praca Publica Integrada', 'slug' => 'praca-publica-integrada', 'category' => 'urbanismo-espaco-publico', 'price' => 290000, 'featured' => false],
            ['name' => 'Parque Urbano de Lazer', 'slug' => 'parque-urbano-lazer', 'category' => 'urbanismo-espaco-publico', 'price' => 570000, 'featured' => false],
            ['name' => 'Mercado Municipal Coberto', 'slug' => 'mercado-municipal-coberto', 'category' => 'urbanismo-espaco-publico', 'price' => 680000, 'featured' => false],
            ['name' => 'Terminal Rodoviario', 'slug' => 'terminal-rodoviario', 'category' => 'urbanismo-espaco-publico', 'price' => 1350000, 'featured' => false],
            ['name' => 'Centro Comunitario Multifuncional', 'slug' => 'centro-comunitario-multifuncional', 'category' => 'urbanismo-espaco-publico', 'price' => 430000, 'featured' => false],
        ];

        $defaultColors = [
            ['name' => 'Contemporaneo Claro', 'hex_code' => '#E5E7EB', 'sort_order' => 1],
            ['name' => 'Moderno Neutro', 'hex_code' => '#D1D5DB', 'sort_order' => 2],
            ['name' => 'Terroso Natural', 'hex_code' => '#B45309', 'sort_order' => 3],
        ];

        $printAreas = [
            ['name' => 'Fachada Principal', 'position' => 'front', 'description' => 'Vista principal da proposta arquitetonica.', 'sort_order' => 1],
            ['name' => 'Planta de Distribuicao', 'position' => 'back', 'description' => 'Organizacao interna dos ambientes e circulacao.', 'sort_order' => 2],
        ];

        foreach ($projects as $index => $projectData) {
            $category = Category::where('slug', $projectData['category'])->first();

            if (!$category) {
                continue;
            }

            $product = Product::updateOrCreate(
                ['slug' => $projectData['slug']],
                [
                    'uuid' => Str::uuid()->toString(),
                    'name' => $projectData['name'],
                    'description' => 'Briefing tecnico com IA para gerar imagem da casa e planta arquitetonica com base nos seus requisitos.',
                    'price' => $projectData['price'],
                    'min_quantity' => 1,
                    'image_url' => 'https://images.unsplash.com/photo-1512918728675-ed5a9ecdebfd?auto=format&fit=crop&w=1200&q=80',
                    'base_image_url' => 'https://images.unsplash.com/photo-1503387762-592deb58ef4e?auto=format&fit=crop&w=1200&q=80',
                    'design_hint' => 'Descreva estilo, terreno, numero de quartos, necessidades e referencias para gerar casa e planta.',
                    'status' => ProductStatus::ACTIVE->value,
                    'is_featured' => $projectData['featured'],
                    'sort_order' => $index + 1,
                ]
            );

            $product->categories()->syncWithoutDetaching([$category->id]);

            foreach ($defaultColors as $color) {
                $product->colors()->updateOrCreate(
                    ['name' => $color['name']],
                    [
                        'hex_code' => $color['hex_code'],
                        'is_active' => true,
                        'sort_order' => $color['sort_order'],
                        'stock_quantity' => null,
                    ]
                );
            }

            foreach ($printAreas as $area) {
                $product->printAreas()->updateOrCreate(
                    ['name' => $area['name']],
                    [
                        'position' => $area['position'],
                        'description' => $area['description'],
                        'max_width_cm' => null,
                        'max_height_cm' => null,
                        'additional_price' => 0,
                        'is_active' => true,
                        'sort_order' => $area['sort_order'],
                    ]
                );
            }
        }
    }
}
