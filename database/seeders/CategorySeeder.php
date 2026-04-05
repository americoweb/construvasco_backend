<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Papelaria Corporativa',
                'slug' => 'papelaria-corporativa',
                'description' => 'Papelaria corporativa personalizada para escritórios e empresas',
                'sort_order' => 1,
                'children' => [
                    [
                        'name' => 'Cartões de Visita',
                        'slug' => 'cartoes-de-visita',
                        'description' => 'Cartões de visita profissionais impressos em papel de alta qualidade',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Papel Timbrado',
                        'slug' => 'papel-timbrado',
                        'description' => 'Papel timbrado personalizado para correspondência oficial',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Envelopes',
                        'slug' => 'envelopes',
                        'description' => 'Envelopes personalizados para correspondência',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Blocos de Notas',
                        'slug' => 'blocos-de-notas',
                        'description' => 'Blocos de notas e blocos de rascunho personalizados',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Pastas de Apresentação',
                        'slug' => 'pastas-de-apresentacao',
                        'description' => 'Pastas de apresentação personalizadas para documentos',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Marcadores de Livro',
                        'slug' => 'marcadores-de-livro',
                        'description' => 'Marcadores de livro personalizados',
                        'sort_order' => 6,
                    ],
                    [
                        'name' => 'Agendas',
                        'slug' => 'agendas',
                        'description' => 'Agendas personalizadas para escritório e uso corporativo',
                        'sort_order' => 7,
                    ],
                ],
            ],
            [
                'name' => 'Material Promocional Impresso',
                'slug' => 'material-promocional-impresso',
                'description' => 'Material promocional impresso para marketing e eventos',
                'sort_order' => 2,
                'children' => [
                    [
                        'name' => 'Flyers',
                        'slug' => 'flyers',
                        'description' => 'Flyers e panfletos promocionais',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Folhetos Dobrados',
                        'slug' => 'folhetos-dobrados',
                        'description' => 'Folhetos dobrados para informações detalhadas',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Brochuras',
                        'slug' => 'brochuras',
                        'description' => 'Brochuras profissionais para apresentações',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Cartazes',
                        'slug' => 'cartazes',
                        'description' => 'Cartazes promocionais para eventos',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Postais',
                        'slug' => 'postais',
                        'description' => 'Postais personalizados',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Convites',
                        'slug' => 'convites',
                        'description' => 'Convites personalizados para eventos',
                        'sort_order' => 6,
                    ],
                    [
                        'name' => 'Tickets',
                        'slug' => 'tickets',
                        'description' => 'Tickets personalizados para eventos',
                        'sort_order' => 7,
                    ],
                    [
                        'name' => 'Tags/Etiquetas',
                        'slug' => 'tags-etiquetas',
                        'description' => 'Tags e etiquetas personalizadas',
                        'sort_order' => 8,
                    ],
                    [
                        'name' => 'Door Hangers',
                        'slug' => 'door-hangers',
                        'description' => 'Mensagens para porta personalizadas',
                        'sort_order' => 9,
                    ],
                    [
                        'name' => 'Individuais',
                        'slug' => 'individuais',
                        'description' => 'Individuais de mesa personalizados',
                        'sort_order' => 10,
                    ],
                ],
            ],
            [
                'name' => 'Sinalização Interior',
                'slug' => 'sinalizacao-interior',
                'description' => 'Sinalização e displays para ambientes internos',
                'sort_order' => 3,
                'children' => [
                    [
                        'name' => 'Posters',
                        'slug' => 'posters',
                        'description' => 'Posters em vários formatos (A4, A3, A2, A1)',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Adesivos',
                        'slug' => 'adesivos',
                        'description' => 'Adesivos personalizados para paredes e superfícies',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Wobblers',
                        'slug' => 'wobblers',
                        'description' => 'Wobblers promocionais para pontos de venda',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Placas de Mesa',
                        'slug' => 'placas-de-mesa',
                        'description' => 'Placas de mesa e table standees',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Counter Units',
                        'slug' => 'counter-units',
                        'description' => 'Unidades de balcão para exibição',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Info Boards',
                        'slug' => 'info-boards',
                        'description' => 'Quadros informativos personalizados',
                        'sort_order' => 6,
                    ],
                    [
                        'name' => 'Selfie Frames',
                        'slug' => 'selfie-frames',
                        'description' => 'Molduras para selfies em eventos',
                        'sort_order' => 7,
                    ],
                    [
                        'name' => 'ABS Boards',
                        'slug' => 'abs-boards',
                        'description' => 'Placas em ABS personalizadas',
                        'sort_order' => 8,
                    ],
                    [
                        'name' => 'Stands Acrílicos',
                        'slug' => 'stands-acrilicos',
                        'description' => 'Stands e expositores em acrílico para documentos',
                        'sort_order' => 9,
                    ],
                    [
                        'name' => 'Quadros Canvas',
                        'slug' => 'quadros-canvas',
                        'description' => 'Quadros em canvas para decoração e branding',
                        'sort_order' => 10,
                    ],
                    [
                        'name' => 'Foam Boards',
                        'slug' => 'foam-boards',
                        'description' => 'Placas em foam board para sinalização e displays',
                        'sort_order' => 11,
                    ],
                ],
            ],
            [
                'name' => 'Sinalização Exterior',
                'slug' => 'sinalizacao-exterior',
                'description' => 'Sinalização e displays para ambientes externos',
                'sort_order' => 4,
                'children' => [
                    [
                        'name' => 'Pull-Up Banners',
                        'slug' => 'pull-up-banners',
                        'description' => 'Banners pull-up retráteis para eventos',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'X-Banners',
                        'slug' => 'x-banners',
                        'description' => 'Banners em formato X para exteriores',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Pop-Up Banners',
                        'slug' => 'pop-up-banners',
                        'description' => 'Banners pop-up para eventos',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Banners PVC',
                        'slug' => 'banners-pvc',
                        'description' => 'Banners em PVC para uso externo',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Banners de Parede',
                        'slug' => 'banners-de-parede',
                        'description' => 'Banners para fixação em paredes',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Harp Banners',
                        'slug' => 'harp-banners',
                        'description' => 'Banners Harp (Sharkfin/Wind Cheater)',
                        'sort_order' => 6,
                    ],
                    [
                        'name' => 'Correx Boards',
                        'slug' => 'correx-boards',
                        'description' => 'Placas em Correx para sinalização externa',
                        'sort_order' => 7,
                    ],
                    [
                        'name' => 'Contravision',
                        'slug' => 'contravision',
                        'description' => 'Contravision para aplicações especiais',
                        'sort_order' => 8,
                    ],
                    [
                        'name' => 'Snapperframes',
                        'slug' => 'snapperframes',
                        'description' => 'Snapperframes para displays rápidos',
                        'sort_order' => 9,
                    ],
                    [
                        'name' => 'Tear Drop Banners',
                        'slug' => 'tear-drop-banners',
                        'description' => 'Banners em formato tear drop para eventos e promoções',
                        'sort_order' => 10,
                    ],
                ],
            ],
            [
                'name' => 'Eventos e Exposições',
                'slug' => 'eventos-exposicoes',
                'description' => 'Produtos para eventos, exposições e feiras',
                'sort_order' => 5,
                'children' => [
                    [
                        'name' => 'Gazebos',
                        'slug' => 'gazebos',
                        'description' => 'Gazebos totalmente impressos para eventos',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Counter Stands',
                        'slug' => 'counter-stands',
                        'description' => 'Bancadas e stands para eventos',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Stacking Cubes',
                        'slug' => 'stacking-cubes',
                        'description' => 'Cubos empilháveis para displays',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Hanging Mobiles',
                        'slug' => 'hanging-mobiles',
                        'description' => 'Móveis suspensos para decoração',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Entry Form Boxes',
                        'slug' => 'entry-form-boxes',
                        'description' => 'Caixas para formulários de entrada',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Backdrops',
                        'slug' => 'backdrops',
                        'description' => 'Backdrops para eventos e fotos profissionais',
                        'sort_order' => 6,
                    ],
                ],
            ],
           
            [
                'name' => 'Brindes Promocionais',
                'slug' => 'brindes-promocionais',
                'description' => 'Brindes e itens promocionais personalizados',
                'sort_order' => 6,
                'children' => [
                    [
                        'name' => 'USB Sticks',
                        'slug' => 'usb-sticks',
                        'description' => 'USB sticks personalizados',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Canetas Personalizáveis',
                        'slug' => 'canetas-personalizaveis',
                        'description' => 'Canetas personalizáveis para branding',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Guarda-chuvas',
                        'slug' => 'guarda-chuvas',
                        'description' => 'Guarda-chuvas personalizados',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Sacos de Compras',
                        'slug' => 'sacos-de-compras',
                        'description' => 'Sacos de compras reutilizáveis personalizados',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Sacos de Papel',
                        'slug' => 'sacos-de-papel',
                        'description' => 'Sacos de papel personalizados',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Distintivos Magnéticos',
                        'slug' => 'distintivos-magneticos',
                        'description' => 'Distintivos magnéticos personalizados',
                        'sort_order' => 6,
                    ],
                    [
                        'name' => 'Chávenas',
                        'slug' => 'chavenas',
                        'description' => 'Chávenas e canecas personalizadas com sublimação',
                        'sort_order' => 7,
                    ],
                    [
                        'name' => 'Pins',
                        'slug' => 'pins',
                        'description' => 'Pins e broches personalizados',
                        'sort_order' => 8,
                    ],
                    [
                        'name' => 'Mousepads',
                        'slug' => 'mousepads',
                        'description' => 'Mousepads personalizados para escritório',
                        'sort_order' => 9,
                    ],
                    [
                        'name' => 'Bases de Copo',
                        'slug' => 'bases-de-copo',
                        'description' => 'Bases de copo (coasters) personalizadas',
                        'sort_order' => 10,
                    ],
                    [
                        'name' => 'Canetas',
                        'slug' => 'canetas',
                        'description' => 'Canetas personalizadas para branding',
                        'sort_order' => 11,
                    ],
                ],
            ],
            [
                'name' => 'Calendários',
                'slug' => 'calendarios',
                'description' => 'Calendários personalizados para empresas',
                'sort_order' => 7,
                'children' => [
                    [
                        'name' => 'Calendários de Parede',
                        'slug' => 'calendarios-de-parede',
                        'description' => 'Calendários de parede personalizados',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Calendários de Mesa Wiro',
                        'slug' => 'calendarios-de-mesa-wiro',
                        'description' => 'Calendários de mesa com encadernação Wiro',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Calendários Deskpad',
                        'slug' => 'calendarios-deskpad',
                        'description' => 'Calendários deskpad para escritório',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Calendários Tenda',
                        'slug' => 'calendarios-tenda',
                        'description' => 'Calendários em formato tenda',
                        'sort_order' => 4,
                    ],
                ],
            ],
            [
                'name' => 'Embalagem',
                'slug' => 'embalagem',
                'description' => 'Embalagem personalizada para produtos',
                'sort_order' => 8,
                'children' => [
                    [
                        'name' => 'Embalagem Personalizada',
                        'slug' => 'embalagem-personalizada',
                        'description' => 'Embalagem totalmente personalizada',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Paper Bags',
                        'slug' => 'paper-bags',
                        'description' => 'Sacos de papel para embalagem',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Boxes',
                        'slug' => 'boxes',
                        'description' => 'Caixas personalizadas',
                        'sort_order' => 3,
                    ],
                ],
            ],
            [
                'name' => 'Vestuário',
                'slug' => 'vestuario',
                'description' => 'Roupas e acessórios de vestuário personalizados',
                'sort_order' => 9,
                'children' => [
                    [
                        'name' => 'Camisetas',
                        'slug' => 'camisetas',
                        'description' => 'Camisetas personalizadas de alta qualidade',
                        'sort_order' => 1,
                    ],
                    [
                        'name' => 'Camisas Polo',
                        'slug' => 'camisas-polo',
                        'description' => 'Camisas polo profissionais para uniformes',
                        'sort_order' => 2,
                    ],
                    [
                        'name' => 'Moletom',
                        'slug' => 'moletom',
                        'description' => 'Moletons e casacos personalizados',
                        'sort_order' => 3,
                    ],
                    [
                        'name' => 'Bonés',
                        'slug' => 'bones',
                        'description' => 'Bonés personalizados',
                        'sort_order' => 4,
                    ],
                    [
                        'name' => 'Jaquetas',
                        'slug' => 'jaquetas',
                        'description' => 'Jaquetas personalizadas',
                        'sort_order' => 5,
                    ],
                    [
                        'name' => 'Coletes',
                        'slug' => 'coletes',
                        'description' => 'Coletes e coletes de segurança personalizados',
                        'sort_order' => 6,
                    ],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $children = $categoryData['children'] ?? [];
            unset($categoryData['children']);

            // Check if category already exists by slug
            $category = Category::where('slug', $categoryData['slug'])->first();
            if (!$category) {
                $categoryData['uuid'] = Str::uuid();
                $categoryData['is_active'] = true;
                $category = Category::create($categoryData);
            } else {
                // Update existing category
                $category->update($categoryData);
            }

            foreach ($children as $childData) {
                // Check if child category already exists by slug
                $childCategory = Category::where('slug', $childData['slug'])
                    ->where('parent_id', $category->id)
                    ->first();
                
                if (!$childCategory) {
                    $childData['uuid'] = Str::uuid();
                    $childData['parent_id'] = $category->id;
                    $childData['is_active'] = true;
                    Category::create($childData);
                } else {
                    // Update existing child category
                    $childCategory->update($childData);
                }
            }
        }
    }
}
