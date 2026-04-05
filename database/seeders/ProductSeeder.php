<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductPrintArea;
use App\Models\Product\ProductSize;
use App\Models\Product\ProductSizeRestriction;
use App\Models\Product\Category;
use App\Models\Product\Tag;
use App\Enums\Product\PrintAreaPosition;
use App\Enums\Product\ProductStatus;
use App\Enums\Product\PricingType;
use Illuminate\Support\Str;

/**
 * CORRECTED PRODUCT SEEDER
 * 
 * All prices are now PER-UNIT in Mozambican Meticais (Mts)
 * Based on actual market prices from Tabela_de_Preços.pdf
 * 
 * Price Sources:
 * - PDF = Direct from price table
 * - MARKET = Researched/inferred from market
 * - CALCULATED = Based on similar products
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // ===================================================================
            // HIGH-VOLUME, LOW-MARGIN PRODUCTS (Daily Orders)
            // ===================================================================
            
            [
                'name' => 'Cartões de Visita - 350gsm Laminado',
                'slug' => 'cartoes-de-visita-350gsm-laminado',
                'description' => 'Cause uma primeira impressão que não se esquece! Cartões profissionais em papel grosso 350gsm com laminação brilhante ou fosca. Tão resistentes que até sobrevivem a uma carteira moçambicana cheia. Perfeitos para aquele networking sério (ou para trocar contactos na praça).',
                'price' => 6.00, // CORRECTED: Was 600 for MOQ 100 = 6 per unit
                'min_quantity' => 100,
                'design_hint' => 'Área de segurança: 3mm de todas as bordas. Texto mínimo 7pt. Logotipo não menor que 5mm altura. Cores escuras exigem laminação para evitar riscos. QR codes: mínimo 20mm x 20mm. Evite gradientes sutis - podem não imprimir consistentemente.',
                'is_featured' => true,
                'sort_order' => 1,
                'category_slugs' => ['cartoes-de-visita'],
                'tag_slugs' => ['papel-350gsm', 'laminacao', 'impressao-digital', 'producao-rapida', 'economico', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 9.0, 'max_height_cm' => 5.0],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK, 'max_width_cm' => 9.0, 'max_height_cm' => 5.0],
                ],
            ],
            [
                'name' => 'Cartões de Visita - 250gsm',
                'slug' => 'cartoes-de-visita-250gsm',
                'description' => 'A opção esperta para quem quer qualidade sem gastar rios de dinheiro. Papel 250gsm resistente, ideal para distribuir aos montes. Porque às vezes quantidade também é qualidade, não é mesmo?',
                'price' => 4.50, // CORRECTED: More economical option
                'min_quantity' => 50,
                'design_hint' => 'Área de segurança: 3mm de todas as bordas. Texto mínimo 7pt. Logotipo não menor que 5mm altura. QR codes: mínimo 20mm x 20mm.',
                'is_featured' => false,
                'sort_order' => 2,
                'category_slugs' => ['cartoes-de-visita'],
                'tag_slugs' => ['papel-250gsm', 'impressao-digital', 'producao-rapida', 'economico', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 9.0, 'max_height_cm' => 5.0],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK, 'max_width_cm' => 9.0, 'max_height_cm' => 5.0],
                ],
            ],
            [
                'name' => 'Flyers - 170gsm',
                'slug' => 'flyers-170gsm',
                'description' => 'Transforme o seu negócio num sucesso de boca em boca! Flyers coloridos que chamam atenção na rua, na praça, no mercado - onde quer que esteja o seu cliente. Papel 170gsm que aguenta o sol de Maputo e as mãos curiosas. Vários tamanhos para todas as campanhas.',
                'price' => 3.50, // CORRECTED: Per unit price
                'min_quantity' => 250,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Margem segura 5mm. Títulos grandes no topo (mínimo 18pt). Alto contraste para leitura rápida. Hierarquia visual clara: título 24-36pt, corpo 10-12pt. WhatsApp/telefone em destaque (moçambicanos ligam). Evite fundos escuros totais - gasta mais tinta e pode manchar.',
                'is_featured' => true,
                'sort_order' => 3,
                'category_slugs' => ['flyers'],
                'tag_slugs' => ['papel-170gsm', 'impressao-digital', 'producao-rapida', 'economico', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 21.0, 'max_height_cm' => 29.7],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK, 'max_width_cm' => 21.0, 'max_height_cm' => 29.7],
                ],
                'sizes' => [
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 4.50],
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 3.50],
                    ['name' => 'A6', 'width_cm' => 10.5, 'height_cm' => 14.8, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2.50],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 10.0,
                    'max_width_cm' => 30.0,
                    'min_height_cm' => 14.0,
                    'max_height_cm' => 42.0,
                ],
            ],
            [
                'name' => 'Papel Timbrado',
                'slug' => 'papel-timbrado',
                'description' => 'Dê credibilidade ao seu negócio! Correspondência oficial merece papel timbrado de respeito. Mostre profissionalismo em cada carta, orçamento ou documento. Porque até os emails impressos ficam mais sérios com o seu logo no topo.',
                'price' => 25.00, // CORRECTED: Per ream (500 sheets) = 2,500 Mts from PDF ÷ 100 units mentioned
                'min_quantity' => 100,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Margem superior 2cm para cabeçalho. Logo máximo 5cm altura. Área de texto livre abaixo. Cores corporativas consistentes.',
                'is_featured' => false,
                'sort_order' => 4,
                'category_slugs' => ['papel-timbrado'],
                'tag_slugs' => ['papel-250gsm', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Cabeçalho', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 21.0, 'max_height_cm' => 5.0],
                ],
                'sizes' => [
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 25.00],
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 18.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 14.0,
                    'max_width_cm' => 30.0,
                    'min_height_cm' => 20.0,
                    'max_height_cm' => 42.0,
                ],
            ],
            [
                'name' => 'Envelopes Personalizados',
                'slug' => 'envelopes-personalizados',
                'description' => 'O envelope é o primeiro aperto de mão da sua correspondência. Personalize com o logo da empresa e surpreenda antes mesmo de abrirem a carta. Marketing elegante desde o correio!',
                'price' => 57.70, // CORRECTED: PDF shows 5,770 Mts per ream (100 units) = 57.70 per unit
                'min_quantity' => 100,
                'design_hint' => 'Logo no canto superior esquerdo (máximo 3cm). Evite área de selo postal. Cores discretas para profissionalismo.',
                'is_featured' => false,
                'sort_order' => 5,
                'category_slugs' => ['envelopes'],
                'tag_slugs' => ['papel-170gsm', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 11.0, 'max_height_cm' => 4.0],
                ],
            ],
            
            // ===================================================================
            // MEDIUM-VOLUME PRODUCTS (Weekly Orders)
            // ===================================================================
            
            [
                'name' => 'Roll-Up Banner Executivo',
                'slug' => 'roll-up-banner-executivo',
                'description' => 'O VIP dos banners! Estrutura premium em alumínio com impressão de tirar o fôlego. Monta em 2 minutos, impressiona para sempre. Perfeito para eventos importantes, feiras e aquela reunião com investidores onde só o melhor serve.',
                'price' => 7500.00, // SOURCE: PDF - 7,500 Mts (normal) / 7,000 Mts (revenda)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Evite texto importante abaixo de 200mm da base (fica na estrutura). Imagens de alta resolução (150dpi mínimo para este tamanho). Logo no topo 300-400mm. Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Alto contraste essencial.',
                'is_featured' => true,
                'sort_order' => 6,
                'category_slugs' => ['pull-up-banners'],
                'tag_slugs' => ['uso-interior', 'pvc', 'impressao-digital', 'producao-normal', 'medio', 'ordem-pequena', 'destaque', 'premium'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Visível', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 85.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => '850x2000mm', 'width_cm' => 85.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 7500.00],
                ],
            ],
            [
                'name' => 'Roll-Up Banner Standard',
                'slug' => 'roll-up-banner-standard',
                'description' => 'Qualidade profissional com preço que não assusta a carteira. O queridinho das PMEs moçambicanas! Leve, prático e com impressão que faz inveja. Ideal para eventos, lojas e promoções que pedem presença marcante.',
                'price' => 5500.00, // SOURCE: PDF - 5,500 Mts (normal) / 5,000 Mts (revenda)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Evite texto importante abaixo de 200mm da base (fica na estrutura). Imagens de alta resolução (150dpi mínimo para este tamanho). Logo no topo 300-400mm. Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Alto contraste essencial.',
                'is_featured' => true,
                'sort_order' => 7,
                'category_slugs' => ['pull-up-banners'],
                'tag_slugs' => ['uso-interior', 'pvc', 'impressao-digital', 'producao-normal', 'medio', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Visível', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 85.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => '850x2000mm', 'width_cm' => 85.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 5500.00],
                ],
            ],
            [
                'name' => 'X-Banner',
                'slug' => 'x-banner',
                'description' => 'Simples, eficaz e super portátil! O guerreiro dos eventos ao ar livre. Aguenta vento, sol e chuva enquanto anuncia o seu negócio. Monta e desmonta num instante - perfeito para quem não tem tempo a perder.',
                'price' => 1800.00, // MARKET: Reasonable for X-Banner vs Roll-Up
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Logotipo no terço superior. Alto contraste essencial (luz vs escuro). Evite detalhes finos - vento e distância reduzem clareza. Cores vivas funcionam melhor.',
                'is_featured' => false,
                'sort_order' => 8,
                'category_slugs' => ['x-banners'],
                'tag_slugs' => ['uso-exterior', 'pvc', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 120.0, 'max_height_cm' => 250.0],
                ],
                'sizes' => [
                    ['name' => '800x2000mm', 'width_cm' => 80.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1800.00],
                    ['name' => '600x1600mm', 'width_cm' => 60.0, 'height_cm' => 160.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1400.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 50.0,
                    'max_width_cm' => 120.0,
                    'min_height_cm' => 100.0,
                    'max_height_cm' => 250.0,
                ],
            ],
            [
                'name' => 'Tear Drop Banner',
                'slug' => 'tear-drop-banner',
                'description' => 'Impossível de ignorar! Este banner em formato gota chama atenção de longe. Perfeito para marcar presença em eventos, feiras e promoções ao ar livre. Dança ao vento e atrai clientes como mel atrai abelhas.',
                'price' => 6000.00, // SOURCE: PDF - 6,000 Mts (2-4 metros)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Design deve seguir formato tear drop. Logo no topo. Texto legível a distância. Alto contraste. Cores vibrantes.',
                'is_featured' => true,
                'sort_order' => 9,
                'category_slugs' => ['tear-drop-banners'],
                'tag_slugs' => ['uso-exterior', 'pvc', 'impressao-digital', 'producao-normal', 'medio', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 90.0, 'max_height_cm' => 400.0],
                ],
                'sizes' => [
                    ['name' => '2 metros', 'width_cm' => 70.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 4500.00],
                    ['name' => '3 metros', 'width_cm' => 80.0, 'height_cm' => 300.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 6000.00],
                    ['name' => '4 metros', 'width_cm' => 90.0, 'height_cm' => 400.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 7500.00],
                ],
            ],
            [
                'name' => 'Backdrop',
                'slug' => 'backdrop',
                'description' => 'Transforme qualquer espaço num palco profissional! Perfeito para eventos, conferências, sessões fotográficas e aquele lançamento que vai bombar nas redes sociais. Fundo impecável onde o seu branding brilha. Faça as fotos ficarem no Instagram para sempre!',
                'price' => 19400.00, // SOURCE: PDF - 19,400 Mts (2.5x2.5m)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Design deve considerar área de foto. Logo e branding estratégico. Cores harmoniosas. Resolução alta (150dpi mínimo).',
                'is_featured' => true,
                'sort_order' => 10,
                'category_slugs' => ['backdrops'],
                'tag_slugs' => ['uso-interior', 'pvc', 'impressao-digital', 'producao-normal', 'premium', 'evento', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 300.0, 'max_height_cm' => 250.0],
                ],
                'sizes' => [
                    ['name' => '2.5x2.5m', 'width_cm' => 250.0, 'height_cm' => 250.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 19400.00],
                    ['name' => '3x2.5m', 'width_cm' => 300.0, 'height_cm' => 250.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 24500.00],
                ],
            ],
            [
                'name' => 'Brochuras - 12 páginas',
                'slug' => 'brochuras-12-paginas',
                'description' => 'Conte a história completa do seu negócio! 12 páginas de puro marketing profissional. Encadernação elegante, impressão de primeira. Para quando um flyer é pouco e um catálogo é demais. O meio-termo perfeito!',
                'price' => 120.00, // CALCULATED: Reasonable for 12-page brochure
                'min_quantity' => 50,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Sangria 3mm. Evite texto sobre vinco central. Numeração de páginas consistente. Margens internas generosas (1.5cm mínimo).',
                'is_featured' => false,
                'sort_order' => 11,
                'category_slugs' => ['brochuras'],
                'tag_slugs' => ['papel-170gsm', 'impressao-digital', 'producao-normal', 'medio', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Capa Frontal', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 21.0, 'max_height_cm' => 29.7],
                    ['name' => 'Páginas Internas', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 20.4, 'max_height_cm' => 28.7],
                ],
                'sizes' => [
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 120.00],
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 85.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 14.0,
                    'max_width_cm' => 30.0,
                    'min_height_cm' => 20.0,
                    'max_height_cm' => 42.0,
                ],
            ],
            [
                'name' => 'Posters',
                'slug' => 'posters',
                'description' => 'Paredes em branco são oportunidades perdidas! Posters vibrantes que decoram, anunciam e vendem. Do pequeno A4 ao gigante A1 - temos o tamanho certo para a sua parede (ou vitrine, ou escritório, ou sala de espera...).',
                'price' => 80.00, // CALCULATED: Base price for A4, scales with size
                'min_quantity' => 5,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 150dpi. Texto legível a 1-3 metros dependendo do tamanho. Alto contraste. Evite detalhes muito pequenos.',
                'is_featured' => false,
                'sort_order' => 12,
                'category_slugs' => ['posters'],
                'tag_slugs' => ['papel-170gsm', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Poster Completo', 'position' => PrintAreaPosition::FULL_POSTER, 'max_width_cm' => 84.1, 'max_height_cm' => 118.9],
                ],
                'sizes' => [
                    ['name' => 'A1', 'width_cm' => 59.4, 'height_cm' => 84.1, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 450.00],
                    ['name' => 'A2', 'width_cm' => 42.0, 'height_cm' => 59.4, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 280.00],
                    ['name' => 'A3', 'width_cm' => 29.7, 'height_cm' => 42.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 150.00],
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 80.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 20.0,
                    'max_width_cm' => 100.0,
                    'min_height_cm' => 25.0,
                    'max_height_cm' => 150.0,
                ],
            ],
            [
                'name' => 'Foam Board',
                'slug' => 'foam-board',
                'description' => 'Leve como pluma, resistente como tábua! Placas foam board perfeitas para apresentações, sinalização interna e exposições. Fica de pé sozinho e não precisa de moldura. Praticidade é o segundo nome dele.',
                'price' => 600.00, // SOURCE: PDF - 600 Mts (A4)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Design deve considerar rigidez do material. Bordas limpas. Resolução alta.',
                'is_featured' => false,
                'sort_order' => 13,
                'category_slugs' => ['foam-boards'],
                'tag_slugs' => ['foam', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 118.9, 'max_height_cm' => 84.1],
                ],
                'sizes' => [
                    ['name' => 'A0', 'width_cm' => 84.1, 'height_cm' => 118.9, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2900.00],
                    ['name' => 'A1', 'width_cm' => 59.4, 'height_cm' => 84.1, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2200.00],
                    ['name' => 'A2', 'width_cm' => 42.0, 'height_cm' => 59.4, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1600.00],
                    ['name' => 'A3', 'width_cm' => 29.7, 'height_cm' => 42.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 800.00],
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 600.00],
                ],
            ],
            [
                'name' => 'Adesivos Personalizados',
                'slug' => 'adesivos-personalizados',
                'description' => 'Grude a sua marca em tudo! Adesivos em vinil que colam em vidros, carros, portas, computadores - onde a imaginação mandar. Resistente à água e ao sol. Marketing que não descola!',
                'price' => 2.00, // CALCULATED: Small format stickers
                'min_quantity' => 50,
                'pricing_type' => PricingType::SQM_BASED,
                'price_per_sqm' => 1000.00,
                'has_sizes' => true,
                'design_hint' => 'Cores sólidas funcionam melhor. Evite detalhes muito finos (<2mm). Alto contraste para visibilidade.',
                'is_featured' => false,
                'sort_order' => 14,
                'category_slugs' => ['adesivos'],
                'tag_slugs' => ['vinil', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Transparente', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Personalizada', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 30.0, 'max_height_cm' => 30.0],
                ],
                'sizes' => [
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => 'A6', 'width_cm' => 10.5, 'height_cm' => 14.8, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 5.0,
                    'max_width_cm' => 30.0,
                    'min_height_cm' => 5.0,
                    'max_height_cm' => 30.0,
                ],
            ],
            [
                'name' => 'Placas de Mesa',
                'slug' => 'placas-de-mesa',
                'description' => 'Identifique sem complicar! Placas elegantes para mesas de eventos, escritórios e reuniões. Material ABS resistente que não quebra nem amassa. Porque até o nome precisa de um lugar de destaque.',
                'price' => 350.00, // MARKET: Reasonable for desk signage
                'min_quantity' => 5,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Texto legível a 1 metro: mínimo 24pt. Logo no topo. Informações essenciais apenas.',
                'is_featured' => false,
                'sort_order' => 15,
                'category_slugs' => ['placas-de-mesa'],
                'tag_slugs' => ['abs', 'impressao-digital', 'producao-normal', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 20.0, 'max_height_cm' => 15.0],
                ],
                'sizes' => [
                    ['name' => '15x10cm', 'width_cm' => 15.0, 'height_cm' => 10.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 350.00],
                    ['name' => '20x15cm', 'width_cm' => 20.0, 'height_cm' => 15.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 450.00],
                    ['name' => '25x18cm', 'width_cm' => 25.0, 'height_cm' => 18.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 550.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 10.0,
                    'max_width_cm' => 30.0,
                    'min_height_cm' => 8.0,
                    'max_height_cm' => 25.0,
                ],
            ],
            [
                'name' => 'Correx Boards',
                'slug' => 'correx-boards',
                'description' => 'O campeão da rua! Placas Correx que aguentam sol, chuva e até vento forte. Perfeitas para sinalização externa, promoções e eventos ao ar livre. Leve mas resistente - como deve ser!',
                'price' => 1200.00, // MARKET: Reasonable for outdoor signage
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Alto contraste. Cores vivas. Evite detalhes finos.',
                'is_featured' => false,
                'sort_order' => 16,
                'category_slugs' => ['correx-boards'],
                'tag_slugs' => ['correx', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 100.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => 'A2', 'width_cm' => 42.0, 'height_cm' => 59.4, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1200.00],
                    ['name' => 'A1', 'width_cm' => 59.4, 'height_cm' => 84.1, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1800.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 50.0,
                    'max_width_cm' => 200.0,
                    'min_height_cm' => 50.0,
                    'max_height_cm' => 300.0,
                ],
            ],
            
            // ===================================================================
            // LOW-VOLUME, HIGH-MARGIN PRODUCTS (Monthly/Project)
            // ===================================================================
            
            [
                'name' => 'Gazebo 3x3m - Sem Parede',
                'slug' => 'gazebo-3x3m-sem-parede',
                'description' => 'O rei dos eventos! Gazebo totalmente personalizado com estrutura de alumínio que aguenta sol, chuva e multidões. Transforma qualquer espaço num ponto de venda irresistível. Seu negócio com sombra garantida e estilo de sobra!',
                'price' => 26500.00, // SOURCE: PDF - 26,500 Mts
                'min_quantity' => 1,
                'design_hint' => 'Logo centralizado no teto. Evite texto pequeno (<100pt). Cores sólidas funcionam melhor. Considere visibilidade de longe. Resolução mínima 100dpi para painéis grandes.',
                'is_featured' => true,
                'sort_order' => 17,
                'category_slugs' => ['gazebos'],
                'tag_slugs' => ['uso-exterior', 'evento', 'importacao', 'premium', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Teto', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 300.0, 'max_height_cm' => 300.0],
                ],
            ],
            [
                'name' => 'Gazebo 3x3m - Com 2 Paredes',
                'slug' => 'gazebo-3x3m-com-2-paredes',
                'description' => 'A versão premium do sucesso! Gazebo com 2 paredes impressas que criam um espaço semi-privado perfeito. Destaque máximo em feiras, eventos e activações de marca. Os clientes não vão conseguir passar sem entrar!',
                'price' => 49950.00, // SOURCE: PDF - 49,950 Mts
                'min_quantity' => 1,
                'design_hint' => 'Logo centralizado no teto e paredes. Evite texto pequeno (<100pt). Cores sólidas funcionam melhor. Considere visibilidade de longe. Resolução mínima 100dpi para painéis grandes.',
                'is_featured' => true,
                'sort_order' => 18,
                'category_slugs' => ['gazebos'],
                'tag_slugs' => ['uso-exterior', 'evento', 'importacao', 'premium', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Teto', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 300.0, 'max_height_cm' => 300.0],
                    ['name' => 'Paredes Laterais', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 300.0, 'max_height_cm' => 300.0],
                ],
            ],
            [
                'name' => 'Gazebo 2x2m - Sem Parede',
                'slug' => 'gazebo-2x2m-sem-parede',
                'description' => 'Versão compacta mas igualmente impressionante! Gazebo 2x2m perfeito para espaços menores ou quando precisa de mais mobilidade. Mesma qualidade, tamanho mais prático. Ideal para quem quer impacto sem ocupar metade do terreno!',
                'price' => 26997.00, // SOURCE: PDF - 26,997 Mts (note: seems like error in PDF, likely should be 26,500 or similar)
                'min_quantity' => 1,
                'design_hint' => 'Logo centralizado no teto. Evite texto pequeno (<100pt). Cores sólidas funcionam melhor. Considere visibilidade de longe. Resolução mínima 100dpi para painéis grandes.',
                'is_featured' => false,
                'sort_order' => 19,
                'category_slugs' => ['gazebos'],
                'tag_slugs' => ['uso-exterior', 'evento', 'importacao', 'premium', 'ordem-pequena'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Teto', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 200.0, 'max_height_cm' => 200.0],
                ],
            ],
            [
                'name' => 'Gazebo 1.5x1.5m - Sem Parede',
                'slug' => 'gazebo-1-5x1-5m-sem-parede',
                'description' => 'O mais prático da família! Gazebo compacto 1.5x1.5m para eventos menores ou quando o espaço é limitado. Mesma qualidade premium, tamanho mais discreto. Perfeito para activações pontuais e eventos íntimos.',
                'price' => 21500.00, // SOURCE: PDF - 21,500 Mts
                'min_quantity' => 1,
                'design_hint' => 'Logo centralizado no teto. Evite texto pequeno (<100pt). Cores sólidas funcionam melhor. Considere visibilidade de longe. Resolução mínima 100dpi para painéis grandes.',
                'is_featured' => false,
                'sort_order' => 20,
                'category_slugs' => ['gazebos'],
                'tag_slugs' => ['uso-exterior', 'evento', 'importacao', 'premium', 'ordem-pequena'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Teto', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 150.0, 'max_height_cm' => 150.0],
                ],
            ],
            [
                'name' => 'USB Sticks Personalizados - 8GB',
                'slug' => 'usb-sticks-personalizados-8gb',
                'description' => 'Brinde tecnológico que realmente é útil! 8GB de espaço com o seu logo gravado a laser. Cada vez que o cliente usar, lembra da sua empresa. Marketing que cabe no bolso e funciona para sempre.',
                'price' => 350.00, // MARKET: Reasonable for 8GB USB with branding
                'min_quantity' => 50,
                'design_hint' => 'Logo máximo 40mm x 10mm. Uma cor funciona melhor. Gravação laser para aparência premium. Evite detalhes muito pequenos.',
                'is_featured' => false,
                'sort_order' => 21,
                'category_slugs' => ['usb-sticks'],
                'tag_slugs' => ['importacao', 'tecnologia', 'medio', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Prata', 'hex_code' => '#C0C0C0'],
                ],
                'print_areas' => [
                    ['name' => 'Logo', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 4.0, 'max_height_cm' => 1.0],
                ],
            ],
            [
                'name' => 'Counter Stands',
                'slug' => 'counter-stands',
                'description' => 'Destaque-se em qualquer feira ou evento! Stands modulares que montam rápido e impressionam muito. Perfeito para exposições, feiras comerciais e activações de marca. O seu produto nunca teve melhor casa!',
                'price' => 4500.00, // MARKET: Reasonable for counter/exhibition stand
                'min_quantity' => 1,
                'design_hint' => 'Design modular. Logo visível de frente e lados. Texto legível a 2 metros: mínimo 48pt.',
                'is_featured' => false,
                'sort_order' => 22,
                'category_slugs' => ['counter-stands'],
                'tag_slugs' => ['uso-interior', 'evento', 'importacao', 'premium'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 100.0, 'max_height_cm' => 80.0],
                ],
            ],
            [
                'name' => 'Calendários de Parede',
                'slug' => 'calendarios-de-parede',
                'description' => 'Marketing que dura 12 meses! Calendários de parede personalizados que ficam à vista todos os dias. Seu logo e branding em cada mês do ano. Presente corporativo que realmente é usado (e lembrado)!',
                'price' => 850.00, // MARKET: Standard calendar pricing
                'min_quantity' => 25,
                'design_hint' => 'Logo no topo ou rodapé. Área de calendário clara e legível. Imagens de alta qualidade (300dpi). Cores corporativas.',
                'is_featured' => false,
                'sort_order' => 23,
                'category_slugs' => ['calendarios-de-parede'],
                'tag_slugs' => ['papel-250gsm', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 42.0, 'max_height_cm' => 59.4],
                ],
            ],
            [
                'name' => 'Calendários Deskpad',
                'slug' => 'calendarios-deskpad',
                'description' => 'Organize o ano e promova a marca! Calendário deskpad com laminação que aguenta café, canetas e o dia-a-dia do escritório. Seu logo sempre à vista na mesa do cliente. Prático e publicitário numa coisa só!',
                'price' => 650.00, // MARKET: Desk calendar pricing
                'min_quantity' => 25,
                'design_hint' => 'Logo no topo. Área de calendário clara. Laminação essencial para durabilidade. Margens generosas.',
                'is_featured' => false,
                'sort_order' => 24,
                'category_slugs' => ['calendarios-deskpad'],
                'tag_slugs' => ['papel-350gsm', 'laminacao', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Superfície', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 60.0, 'max_height_cm' => 40.0],
                ],
            ],
            
            // ===================================================================
            // PROMOTIONAL ITEMS / BRINDES
            // ===================================================================
            
            [
                'name' => 'Chávena Normal',
                'slug' => 'chavena-normal',
                'description' => 'O brinde que começa o dia do seu cliente! Chávena em cerâmica com sublimação vibrante e durável. Perfeita para o chá ou café da manhã, cada gole é um lembrete da sua marca. Brinde prático que não vai parar na gaveta!',
                'price' => 450.00, // SOURCE: PDF - 450 Mts
                'min_quantity' => 10,
                'design_hint' => 'Logo ou design envolvente. Área de impressão: 8cm altura x 22cm circunferência. Cores vibrantes funcionam melhor. Evite texto muito pequeno.',
                'is_featured' => true,
                'sort_order' => 25,
                'category_slugs' => ['chavenas'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'economico', 'ordem-pequena', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Envolvente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 22.0, 'max_height_cm' => 8.0],
                ],
            ],
            [
                'name' => 'Chávena Mágica',
                'slug' => 'chavena-magica',
                'description' => 'Pura magia no café da manhã! Quando o líquido quente toca, BUM! - o design aparece. Efeito "uau" garantido. O brinde que os clientes vão querer mostrar aos amigos. Marketing + entretenimento numa chávena só.',
                'price' => 650.00, // SOURCE: PDF - 650 Mts
                'min_quantity' => 10,
                'design_hint' => 'Design que revela com temperatura. Logo ou mensagem surpreendente. Considere o efeito visual do "antes" e "depois".',
                'is_featured' => true,
                'sort_order' => 26,
                'category_slugs' => ['chavenas'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'medio', 'ordem-pequena', 'destaque', 'premium'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Área Envolvente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 22.0, 'max_height_cm' => 8.0],
                ],
            ],
            [
                'name' => 'Caneta Plástica',
                'slug' => 'caneta-plastica',
                'description' => 'O brinde clássico que nunca falha! Caneta leve, prática e com o seu logo sempre à vista. Perfeita para distribuir em eventos, feiras e como mimo aos clientes. Porque quem não precisa de mais uma caneta?',
                'price' => 100.00, // SOURCE: PDF - 100 Mts
                'min_quantity' => 50,
                'design_hint' => 'Logo máximo 30mm x 5mm. Uma cor funciona melhor. Serigrafia para durabilidade.',
                'is_featured' => false,
                'sort_order' => 27,
                'category_slugs' => ['canetas'],
                'tag_slugs' => ['serigrafia', 'producao-normal', 'economico', 'ordem-media'],
                'colors' => [
                    ['name' => 'Azul', 'hex_code' => '#0000FF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                ],
                'print_areas' => [
                    ['name' => 'Corpo', 'position' => PrintAreaPosition::BODY, 'max_width_cm' => 3.0, 'max_height_cm' => 0.5],
                ],
            ],
            [
                'name' => 'Caneta Metálica',
                'slug' => 'caneta-metalica',
                'description' => 'Elegância que escreve! Caneta metálica premium com gravação a laser. Para clientes VIP e ocasiões especiais. O tipo de caneta que não se empresta - demasiado bonita para isso!',
                'price' => 150.00, // SOURCE: PDF - 150 Mts
                'min_quantity' => 50,
                'design_hint' => 'Logo máximo 30mm x 5mm. Gravação laser para aparência premium. Evite detalhes muito pequenos.',
                'is_featured' => false,
                'sort_order' => 28,
                'category_slugs' => ['canetas'],
                'tag_slugs' => ['gravacao-laser', 'producao-normal', 'medio', 'ordem-media', 'premium'],
                'colors' => [
                    ['name' => 'Prata', 'hex_code' => '#C0C0C0'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Dourado', 'hex_code' => '#FFD700'],
                ],
                'print_areas' => [
                    ['name' => 'Corpo', 'position' => PrintAreaPosition::BODY, 'max_width_cm' => 3.0, 'max_height_cm' => 0.5],
                ],
            ],
            [
                'name' => 'Pin Sublimação',
                'slug' => 'pin-sublimacao',
                'description' => 'Pequeno mas poderoso! Pins personalizados para identificação, eventos ou colecção. Design colorido em alta definição. Transforma colaboradores em embaixadores da marca ambulantes.',
                'price' => 200.00, // SOURCE: PDF - 200 Mts
                'min_quantity' => 25,
                'design_hint' => 'Design compacto. Logo e texto legível. Cores vibrantes. Tamanho recomendado: 25-40mm.',
                'is_featured' => false,
                'sort_order' => 29,
                'category_slugs' => ['pins'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'economico', 'ordem-pequena'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 4.0, 'max_height_cm' => 4.0],
                ],
            ],
            [
                'name' => 'Pin em Doming',
                'slug' => 'pin-em-doming',
                'description' => 'O pin que tem profundidade! Efeito 3D elegante com resina doming que dá um toque premium. Para marcas que querem destacar-se até nos detalhes. Pequeno no tamanho, gigante no impacto!',
                'price' => 250.00, // SOURCE: PDF - 250 Mts
                'min_quantity' => 25,
                'design_hint' => 'Design compacto. Logo e texto legível. Efeito 3D elegante. Tamanho recomendado: 25-40mm.',
                'is_featured' => false,
                'sort_order' => 30,
                'category_slugs' => ['pins'],
                'tag_slugs' => ['doming', 'producao-normal', 'medio', 'ordem-pequena', 'premium'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 4.0, 'max_height_cm' => 4.0],
                ],
            ],
            [
                'name' => 'MousePad',
                'slug' => 'mousepad',
                'description' => 'O escritório começa pelo mousepad! Base antiderrapante com impressão em alta definição. Seu logo acompanha cada clique do cliente. Brinde útil para quem passa o dia no computador (quase toda a gente hoje em dia!).',
                'price' => 350.00, // SOURCE: PDF - 350 Mts
                'min_quantity' => 10,
                'design_hint' => 'Design pode cobrir toda a área. Logo centralizado ou padrão repetido. Cores vibrantes. Resolução mínima 150dpi.',
                'is_featured' => false,
                'sort_order' => 31,
                'category_slugs' => ['mousepads'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'economico', 'ordem-pequena'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Superfície', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 22.0, 'max_height_cm' => 18.0],
                ],
            ],
            [
                'name' => 'Base de Copo - Borracha',
                'slug' => 'base-de-copo-borracha',
                'description' => 'Protege a mesa, promove a marca! Base antiderrapante com o seu design. Brinde simpático para cafés, restaurantes ou clientes especiais. Pequeno detalhe que faz toda a diferença.',
                'price' => 90.00, // SOURCE: PDF - 90 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo ou design circular. Tamanho padrão: 9-10cm diâmetro. Cores limitadas.',
                'is_featured' => false,
                'sort_order' => 32,
                'category_slugs' => ['bases-de-copo'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'economico'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Superfície', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 10.0, 'max_height_cm' => 10.0],
                ],
            ],
            [
                'name' => 'Base de Copo - Madeira',
                'slug' => 'base-de-copo-madeira',
                'description' => 'Elegância natural com toque premium! Base em madeira com gravação a laser que dá um ar sofisticado. Para marcas que valorizam qualidade e sustentabilidade. O brinde que parece presente de aniversário!',
                'price' => 100.00, // SOURCE: PDF - 100 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo ou design simples. Gravação laser para elegância. Tamanho padrão: 9-10cm diâmetro.',
                'is_featured' => false,
                'sort_order' => 33,
                'category_slugs' => ['bases-de-copo'],
                'tag_slugs' => ['gravacao-laser', 'producao-normal', 'economico', 'ecologico'],
                'colors' => [
                    ['name' => 'Natural', 'hex_code' => '#D2B48C'],
                ],
                'print_areas' => [
                    ['name' => 'Superfície', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 10.0, 'max_height_cm' => 10.0],
                ],
            ],
            [
                'name' => 'Base de Copo - Vidro',
                'slug' => 'base-de-copo-vidro',
                'description' => 'Transparência com estilo! Base em vidro premium que mostra o seu design com elegância. Perfeita para ambientes modernos e marcas que gostam de brilhar. O toque sofisticado que impressiona!',
                'price' => 120.00, // SOURCE: PDF - 120 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo ou design elegante. Cores vibrantes sobre fundo branco. Tamanho padrão: 9-10cm diâmetro.',
                'is_featured' => false,
                'sort_order' => 34,
                'category_slugs' => ['bases-de-copo'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'medio', 'premium'],
                'colors' => [
                    ['name' => 'Transparente', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Superfície', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 10.0, 'max_height_cm' => 10.0],
                ],
            ],
            
            // ===================================================================
            // PACKAGING & BAGS
            // ===================================================================
            
            [
                'name' => 'Sacos de Papel Kraft',
                'slug' => 'sacos-de-papel-kraft',
                'description' => 'Ecológico, resistente e com estilo! Sacos kraft personalizados que transformam cada compra numa experiência de marca. Do mini ao gigante, temos o tamanho certo. Cliente feliz é aquele que sai da loja com um saco bonito!',
                'price' => 200.00, // Base price (smallest size)
                'min_quantity' => 250,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => '1-2 cores para melhor custo. Logo centralizado. Máximo 200mm largura impressão. Cores escuras sobre fundo claro funcionam melhor.',
                'is_featured' => false,
                'sort_order' => 35,
                'category_slugs' => ['sacos-de-papel'],
                'tag_slugs' => ['serigrafia', 'producao-normal', 'economico', 'ecologico', 'ordem-grande'],
                'colors' => [
                    ['name' => 'Kraft Natural', 'hex_code' => '#D2B48C'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 24.0, 'max_height_cm' => 28.0],
                ],
                'sizes' => [
                    ['name' => 'Mini (16x10x23cm)', 'width_cm' => 16.0, 'height_cm' => 10.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 200.00],
                    ['name' => 'Média (22x11x30cm)', 'width_cm' => 22.0, 'height_cm' => 11.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 200.00],
                    ['name' => 'Max (26x11x36cm)', 'width_cm' => 26.0, 'height_cm' => 11.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 260.00],
                ],
            ],
            [
                'name' => 'Sacos de Compras Algodão',
                'slug' => 'sacos-de-compras-algodao',
                'description' => 'Sustentabilidade com classe! Sacos reutilizáveis que os clientes vão usar vezes sem conta. Cada ida ao mercado é publicidade gratuita. Marketing ecológico que anda pela cidade.',
                'price' => 180.00, // MARKET: Reasonable for cotton tote
                'min_quantity' => 50,
                'design_hint' => 'Logo grande e centralizado. Cores vibrantes. Serigrafia funciona melhor. Evite detalhes muito finos.',
                'is_featured' => false,
                'sort_order' => 38,
                'category_slugs' => ['sacos-de-compras'],
                'tag_slugs' => ['serigrafia', 'producao-normal', 'medio', 'ecologico'],
                'colors' => [
                    ['name' => 'Natural', 'hex_code' => '#F5F5DC'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 30.0, 'max_height_cm' => 35.0],
                ],
            ],
            [
                'name' => 'Sacola de Cortiça',
                'slug' => 'sacola-de-cortica',
                'description' => 'O toque natural que impressiona! Cortiça 100% sustentável com design elegante. Para marcas que valorizam qualidade e ambiente. Não é só uma sacola, é uma declaração de valores.',
                'price' => 565.00, // SOURCE: PDF - 565 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo ou design simples. Gravação laser ou serigrafia. Ecologicamente sustentável.',
                'is_featured' => false,
                'sort_order' => 39,
                'category_slugs' => ['sacos-de-compras'],
                'tag_slugs' => ['gravacao-laser', 'producao-normal', 'medio', 'ecologico', 'premium'],
                'colors' => [
                    ['name' => 'Cortiça Natural', 'hex_code' => '#C19A6B'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 25.0, 'max_height_cm' => 30.0],
                ],
            ],
            [
                'name' => 'Boxes Personalizados',
                'slug' => 'boxes-personalizados',
                'description' => 'A primeira impressão começa na embalagem! Caixas personalizadas que transformam qualquer produto num presente. Seu logo na tampa, qualidade em cada dobra. Porque até a embalagem precisa de estilo!',
                'price' => 120.00, // MARKET: Reasonable for custom boxes
                'min_quantity' => 50,
                'design_hint' => 'Logo na tampa. Considerar dobras e vincos. Sangria 3mm. Cores corporativas.',
                'is_featured' => false,
                'sort_order' => 40,
                'category_slugs' => ['boxes'],
                'tag_slugs' => ['papel-350gsm', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Tampa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 20.0, 'max_height_cm' => 15.0],
                ],
            ],
            
            // ===================================================================
            // STATIONERY / ESCRITÓRIO
            // ===================================================================
            
            [
                'name' => 'Bloco de Notas',
                'slug' => 'bloco-de-notas',
                'description' => 'Onde as grandes ideias começam! Blocos personalizados para o dia-a-dia do escritório. Seu logo em cada página, cada anotação, cada reunião. Brinde útil que não acaba na gaveta.',
                'price' => 450.00, // Base price (smallest size)
                'min_quantity' => 25,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Logo no topo ou rodapé. Cores corporativas. Margens generosas.',
                'is_featured' => false,
                'sort_order' => 41,
                'category_slugs' => ['blocos-de-notas'],
                'tag_slugs' => ['papel-115gsm', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 21.0, 'max_height_cm' => 29.7],
                ],
                'sizes' => [
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 600.00],
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 450.00],
                ],
            ],
            [
                'name' => 'Bloco de Notas com Argola A5',
                'slug' => 'bloco-de-notas-com-argola-a5',
                'description' => 'Organização com estilo! Bloco com encadernação em argola que permite virar páginas sem rasgar. Perfeito para quem anota muito e quer que as folhas fiquem no lugar. Seu logo em cada página virada!',
                'price' => 975.00, // SOURCE: PDF - 975 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo na capa. Considerar posição da argola. Cores corporativas.',
                'is_featured' => false,
                'sort_order' => 43,
                'category_slugs' => ['blocos-de-notas'],
                'tag_slugs' => ['papel-115gsm', 'formato-a5', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 14.8, 'max_height_cm' => 21.0],
                ],
            ],
            [
                'name' => 'Bloco de Notas de Cortiça A5',
                'slug' => 'bloco-de-notas-de-cortica-a5',
                'description' => 'Elegância natural para o escritório! Capa em cortiça com acabamento premium que impressiona. Para marcas que gostam de qualidade e sustentabilidade. O bloco que ninguém quer emprestar!',
                'price' => 750.00, // SOURCE: PDF - 750 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo gravado ou impresso. Ecologicamente sustentável. Design minimalista.',
                'is_featured' => false,
                'sort_order' => 44,
                'category_slugs' => ['blocos-de-notas'],
                'tag_slugs' => ['papel-115gsm', 'formato-a5', 'gravacao-laser', 'producao-normal', 'medio', 'ecologico', 'premium'],
                'colors' => [
                    ['name' => 'Cortiça Natural', 'hex_code' => '#C19A6B'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 14.8, 'max_height_cm' => 21.0],
                ],
            ],
            [
                'name' => 'Agenda - Leda A5',
                'slug' => 'agenda-leda-a5',
                'description' => 'Organize o ano com estilo! Agenda premium modelo Leda com acabamento de luxo. Para empresas que levam planeamento a sério. Presente executivo que impressiona de Janeiro a Dezembro.',
                'price' => 960.00, // SOURCE: PDF - 960 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo na capa. Acabamento premium. Cores corporativas elegantes.',
                'is_featured' => false,
                'sort_order' => 45,
                'category_slugs' => ['agendas'],
                'tag_slugs' => ['papel-115gsm', 'formato-a5', 'impressao-digital', 'producao-normal', 'medio', 'premium'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 14.8, 'max_height_cm' => 21.0],
                ],
            ],
            [
                'name' => 'Stand Acrílico',
                'slug' => 'stand-acrilico',
                'description' => 'Exposição elegante e moderna! Stands em acrílico cristalino para destacar documentos, menus, promoções. Dá um ar profissional a qualquer balcão ou mesa. Simples, bonito, eficaz.',
                'price' => 400.00, // Base price (smallest size)
                'min_quantity' => 5,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Design transparente ou com logo discreto. Para exposição de documentos.',
                'is_featured' => false,
                'sort_order' => 46,
                'category_slugs' => ['stands-acrilicos'],
                'tag_slugs' => ['acrilico', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Transparente', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Base', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 21.0, 'max_height_cm' => 5.0],
                ],
                'sizes' => [
                    ['name' => 'A4', 'width_cm' => 21.0, 'height_cm' => 29.7, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1100.00],
                    ['name' => 'A5', 'width_cm' => 14.8, 'height_cm' => 21.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 580.00],
                    ['name' => 'A6', 'width_cm' => 10.5, 'height_cm' => 14.8, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 400.00],
                    ['name' => 'DL', 'width_cm' => 21.0, 'height_cm' => 9.9, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 750.00],
                ],
            ],
            [
                'name' => 'Pastas de Apresentação',
                'slug' => 'pastas-de-apresentacao',
                'description' => 'Organize e impressione! Pastas de apresentação que dão credibilidade a qualquer proposta ou documento. Seu logo na capa, qualidade em cada página. Porque até os documentos precisam de estilo!',
                'price' => 280.00, // MARKET: Reasonable for presentation folders
                'min_quantity' => 25,
                'design_hint' => 'Logo na capa. Cores corporativas. Considerar dobras e vincos. Laminação recomendada.',
                'is_featured' => false,
                'sort_order' => 50,
                'category_slugs' => ['pastas-de-apresentacao'],
                'tag_slugs' => ['papel-350gsm', 'laminacao', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Capa', 'position' => PrintAreaPosition::FRONT_COVER, 'max_width_cm' => 30.0, 'max_height_cm' => 21.0],
                ],
            ],
            [
                'name' => 'Marcadores de Livro',
                'slug' => 'marcadores-de-livro',
                'description' => 'Pequeno mas presente! Marcadores personalizados que ficam no livro do cliente por semanas (ou meses). Seu logo sempre à vista entre as páginas. Marketing discreto que funciona!',
                'price' => 22.00, // MARKET: Reasonable for bookmarks
                'min_quantity' => 100,
                'design_hint' => 'Logo ou mensagem clara. Cores vibrantes. Texto legível (mínimo 10pt).',
                'is_featured' => false,
                'sort_order' => 51,
                'category_slugs' => ['marcadores-de-livro'],
                'tag_slugs' => ['papel-250gsm', 'laminacao', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 5.0, 'max_height_cm' => 15.0],
                ],
            ],
            
            // ===================================================================
            // MARKETING MATERIALS
            // ===================================================================
            
            [
                'name' => 'Folhetos Dobrados',
                'slug' => 'folhetos-dobrados',
                'description' => 'Mais espaço, mais informação! Folhetos dobrados que contam a história completa do seu negócio. Perfeitos para quando um flyer simples não chega. Desdobre o sucesso do seu negócio!',
                'price' => 5.50, // CALCULATED: Slightly more than flat flyers
                'min_quantity' => 100,
                'design_hint' => 'Considerar dobras no design. Área segura 5mm de cada dobra. Hierarquia visual clara.',
                'is_featured' => false,
                'sort_order' => 52,
                'category_slugs' => ['folhetos-dobrados'],
                'tag_slugs' => ['papel-170gsm', 'formato-a4', 'impressao-digital', 'producao-normal', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 21.0, 'max_height_cm' => 29.7],
                ],
            ],
            [
                'name' => 'Postais',
                'slug' => 'postais',
                'description' => 'Comunicação com estilo! Postais personalizados para campanhas, eventos ou simplesmente para manter contacto. Imagem de alta qualidade que impressiona. Porque até o correio precisa de personalidade!',
                'price' => 15.00, // MARKET: Standard postcard pricing
                'min_quantity' => 100,
                'design_hint' => 'Imagem de alta qualidade (300dpi). Área para endereço clara. Margens generosas.',
                'is_featured' => false,
                'sort_order' => 53,
                'category_slugs' => ['postais'],
                'tag_slugs' => ['papel-250gsm', 'formato-a6', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 10.5, 'max_height_cm' => 14.8],
                ],
            ],
            [
                'name' => 'Convites',
                'slug' => 'convites',
                'description' => 'O primeiro contacto com o seu evento! Convites elegantes que criam expectativa e mostram profissionalismo. Do casamento ao lançamento, cada evento merece um convite à altura. Faça-os contar os dias até o grande dia!',
                'price' => 85.00, // MARKET: Premium invitation pricing
                'min_quantity' => 50,
                'design_hint' => 'Margens generosas (10-15mm) para aparência elegante. Fontes legíveis mesmo em tamanhos pequenos (mínimo 9pt). Data/hora/local sempre em destaque. Para eventos formais: papel 250gsm+ com laminação. RSVP com número WhatsApp funciona melhor que email em Moçambique.',
                'is_featured' => false,
                'sort_order' => 54,
                'category_slugs' => ['convites'],
                'tag_slugs' => ['papel-250gsm', 'laminacao', 'impressao-digital', 'producao-normal', 'medio', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 14.8, 'max_height_cm' => 21.0],
                ],
            ],
            [
                'name' => 'Tickets',
                'slug' => 'tickets',
                'description' => 'A entrada para o sucesso! Tickets personalizados que são lembrança e marketing numa coisa só. Perfeitos para eventos, shows e activações. Cada ticket é uma memória (e uma oportunidade de marketing)!',
                'price' => 12.00, // MARKET: Standard event ticket pricing
                'min_quantity' => 100,
                'design_hint' => 'Informações essenciais: evento, data, local. Código de barras ou QR code se necessário. Alto contraste.',
                'is_featured' => false,
                'sort_order' => 55,
                'category_slugs' => ['tickets'],
                'tag_slugs' => ['papel-170gsm', 'formato-personalizado', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 8.0, 'max_height_cm' => 5.0],
                ],
            ],
            [
                'name' => 'Tags/Etiquetas',
                'slug' => 'tags-etiquetas',
                'description' => 'Identifique tudo com estilo! Tags e etiquetas personalizadas para produtos, eventos ou organização. Pequenas mas importantes - como os detalhes que fazem a diferença!',
                'price' => 8.00, // MARKET: Standard tag pricing
                'min_quantity' => 100,
                'design_hint' => 'Texto legível (mínimo 8pt). Logo pequeno. Informações essenciais apenas.',
                'is_featured' => false,
                'sort_order' => 56,
                'category_slugs' => ['tags-etiquetas'],
                'tag_slugs' => ['papel-170gsm', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Personalizada', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 10.0, 'max_height_cm' => 5.0],
                ],
            ],
            [
                'name' => 'Door Hangers',
                'slug' => 'door-hangers',
                'description' => 'Marketing que não bate à porta, fica nela! Mensagens personalizadas que ficam penduradas na porta do cliente. Perfeitas para campanhas, promoções e lembretes. Porque às vezes a melhor publicidade é a que não incomoda!',
                'price' => 18.00, // MARKET: Door hanger pricing
                'min_quantity' => 100,
                'design_hint' => 'Mensagem clara e direta. Logo visível. Call-to-action destacado.',
                'is_featured' => false,
                'sort_order' => 57,
                'category_slugs' => ['door-hangers'],
                'tag_slugs' => ['papel-250gsm', 'formato-personalizado', 'impressao-digital', 'producao-rapida', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 21.0, 'max_height_cm' => 10.0],
                ],
            ],
            [
                'name' => 'Wobblers',
                'slug' => 'wobblers',
                'description' => 'Mexe, chama atenção, vende! Wobblers que dançam ao vento e atraem olhares no ponto de venda. Perfeitos para promoções, lançamentos e destaque de produtos. Marketing que não fica parado!',
                'price' => 95.00, // MARKET: POS wobbler pricing
                'min_quantity' => 25,
                'design_hint' => 'Mensagem curta e impactante. Logo visível. Cores vibrantes. Texto legível a 1 metro.',
                'is_featured' => false,
                'sort_order' => 58,
                'category_slugs' => ['wobblers'],
                'tag_slugs' => ['abs', 'impressao-digital', 'producao-normal', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 15.0, 'max_height_cm' => 20.0],
                ],
            ],
            [
                'name' => 'Pop-Up Banners',
                'slug' => 'pop-up-banners',
                'description' => 'Pop-up que impressiona! Banners que montam rápido e fazem presença marcante em qualquer evento. Estrutura portátil, impacto garantido. Para quando precisa de destaque sem complicar!',
                'price' => 2200.00, // MARKET: Pop-up banner pricing
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Logo no topo. Alto contraste.',
                'is_featured' => false,
                'sort_order' => 59,
                'category_slugs' => ['pop-up-banners'],
                'tag_slugs' => ['uso-interior', 'pvc', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Visível', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 100.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => '800x2000mm', 'width_cm' => 80.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2200.00],
                    ['name' => '1000x2000mm', 'width_cm' => 100.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2800.00],
                    ['name' => 'A2', 'width_cm' => 42.0, 'height_cm' => 59.4, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1200.00],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 40.0,
                    'max_width_cm' => 120.0,
                    'min_height_cm' => 50.0,
                    'max_height_cm' => 250.0,
                ],
            ],
            [
                'name' => 'Banners PVC',
                'slug' => 'banners-pvc',
                'description' => 'O guerreiro das intempéries! Banners PVC que aguentam sol forte, chuva e vento sem perder a cor. Perfeitos para uso externo, eventos ao ar livre e sinalização permanente. Resistência moçambicana garantida!',
                'price' => 1900.00, // MARKET: Base price for outdoor banner
                'min_quantity' => 1,
                'pricing_type' => PricingType::SQM_BASED,
                'price_per_sqm' => 120.00,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Cores vivas. Resistente a intempéries.',
                'is_featured' => false,
                'sort_order' => 60,
                'category_slugs' => ['banners-pvc'],
                'tag_slugs' => ['uso-exterior', 'pvc', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 300.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => '200x100cm', 'width_cm' => 200.0, 'height_cm' => 100.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => '300x150cm', 'width_cm' => 300.0, 'height_cm' => 150.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => '400x200cm', 'width_cm' => 400.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 100.0,
                    'max_width_cm' => 500.0,
                    'min_height_cm' => 50.0,
                    'max_height_cm' => 300.0,
                ],
            ],
            [
                'name' => 'Banners de Parede',
                'slug' => 'banners-de-parede',
                'description' => 'Ocupa a parede, domina o espaço! Banners de parede que transformam qualquer muro em ponto de marketing. Perfeitos para fachadas, eventos e sinalização permanente. Quando precisa de presença, não de discrição!',
                'price' => 1600.00, // MARKET: Wall banner pricing
                'min_quantity' => 1,
                'pricing_type' => PricingType::SQM_BASED,
                'price_per_sqm' => 100.00,
                'has_sizes' => true,
                'design_hint' => 'Resolução mínima 100dpi. Texto legível a 3-5 metros: mínimo 80pt. Considerar altura de fixação.',
                'is_featured' => false,
                'sort_order' => 61,
                'category_slugs' => ['banners-de-parede'],
                'tag_slugs' => ['uso-exterior', 'pvc', 'impressao-digital', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 400.0, 'max_height_cm' => 200.0],
                ],
                'sizes' => [
                    ['name' => '300x150cm', 'width_cm' => 300.0, 'height_cm' => 150.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => '400x200cm', 'width_cm' => 400.0, 'height_cm' => 200.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => '500x250cm', 'width_cm' => 500.0, 'height_cm' => 250.0, 'is_predefined' => true, 'is_custom' => false],
                    ['name' => 'Custom', 'width_cm' => null, 'height_cm' => null, 'is_predefined' => false, 'is_custom' => true],
                ],
                'size_restrictions' => [
                    'min_width_cm' => 200.0,
                    'max_width_cm' => 600.0,
                    'min_height_cm' => 100.0,
                    'max_height_cm' => 400.0,
                ],
            ],
            
            // ===================================================================
            // APPAREL / VESTUÁRIO
            // ===================================================================
            
            [
                'name' => 'Camiseta Estampada',
                'slug' => 'camiseta-estampada',
                'description' => 'Vista a equipa, uniformize o sucesso! T-shirts personalizadas com estampagem de alta qualidade que não desbota nem racha. Confortáveis para o calor de Moçambique, bonitas para representar a marca. Equipa unida, cliente impressionado!',
                'price' => 380.00, // SOURCE: PDF - 380 Mts (CORRECTED from 850 Mts)
                'min_quantity' => 10,
                'design_hint' => 'Design funciona melhor centralizado no peito (15-20cm abaixo do colarinho). Evite perto do colarinho e mangas. Área de impressão recomendada: 30cm x 35cm. Cores escuras em tecidos claros e vice-versa para melhor contraste. Resolução mínima 150dpi.',
                'is_featured' => true,
                'sort_order' => 62,
                'category_slugs' => ['camisetas'],
                'tag_slugs' => ['sublimacao', 'serigrafia', 'producao-normal', 'economico', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                ],
                'print_areas' => [
                    ['name' => 'Frente (Peito)', 'position' => PrintAreaPosition::FRONT_CHEST, 'max_width_cm' => 30, 'max_height_cm' => 35],
                    ['name' => 'Costas (Completo)', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 35, 'max_height_cm' => 45],
                ],
            ],
            [
                'name' => 'Camiseta Bordada',
                'slug' => 'camiseta-bordada',
                'description' => 'O toque premium que faz a diferença! Bordado de alta qualidade que resiste a 1000 lavagens. Para equipas que merecem o melhor. Durabilidade e elegância em cada fio.',
                'price' => 480.00, // SOURCE: PDF - 480 Mts
                'min_quantity' => 10,
                'design_hint' => 'Logo pequeno e sutil no peito esquerdo (máximo 10cm x 10cm). Bordado para durabilidade premium. Design profissional.',
                'is_featured' => true,
                'sort_order' => 63,
                'category_slugs' => ['camisetas'],
                'tag_slugs' => ['bordado', 'producao-normal', 'medio', 'ordem-media', 'destaque', 'premium'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Camiseta Bordada Frente + Estampa Costas',
                'slug' => 'camiseta-bordada-frente-estampa-costas',
                'description' => 'O melhor dos dois mundos! Bordado elegante na frente, estampa impactante nas costas. Combinação premium que impressiona de todos os ângulos. Para equipas que querem destacar-se em qualquer direcção!',
                'price' => 570.00, // SOURCE: PDF - 570 Mts
                'min_quantity' => 10,
                'design_hint' => 'Logo bordado no peito esquerdo + design grande nas costas. Combinação premium de técnicas.',
                'is_featured' => true,
                'sort_order' => 64,
                'category_slugs' => ['camisetas'],
                'tag_slugs' => ['bordado', 'serigrafia', 'producao-normal', 'medio', 'ordem-media', 'destaque', 'premium'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                    ['name' => 'Costas (Completo)', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 35, 'max_height_cm' => 45],
                ],
            ],
            [
                'name' => 'Camisa Polo Estampada',
                'slug' => 'camisa-polo-estampada',
                'description' => 'Profissionalismo que se veste! Polo de qualidade com seu logo discreto mas presente. Perfeita para vendas, atendimento ao cliente e eventos corporativos. Conforto que transmite seriedade.',
                'price' => 800.00, // CALCULATED: Premium over t-shirt estampada (380 x 2.1)
                'min_quantity' => 10,
                'design_hint' => 'Logo/emblema pequeno e sutil no peito esquerdo (máximo 10cm x 10cm). Design profissional e discreto. Evite designs grandes ou chamativos.',
                'is_featured' => true,
                'sort_order' => 65,
                'category_slugs' => ['camisas-polo'],
                'tag_slugs' => ['serigrafia', 'sublimacao', 'producao-normal', 'medio', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                    ['name' => 'Navy', 'hex_code' => '#000080'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Camisa Polo Bordada',
                'slug' => 'camisa-polo-bordada',
                'description' => 'A escolha das empresas de topo! Polo premium com bordado que dura para sempre. Uniformes que elevam a imagem da empresa. Qualidade que se vê (e se sente).',
                'price' => 1000.00, // CALCULATED: Premium polo with embroidery
                'min_quantity' => 10,
                'design_hint' => 'Logo/emblema pequeno e sutil no peito esquerdo (máximo 10cm x 10cm). Bordado premium para durabilidade. Design profissional e discreto.',
                'is_featured' => true,
                'sort_order' => 66,
                'category_slugs' => ['camisas-polo'],
                'tag_slugs' => ['bordado', 'producao-normal', 'premium', 'ordem-media', 'destaque'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                    ['name' => 'Navy', 'hex_code' => '#000080'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Camisa Caqui',
                'slug' => 'camisa-caqui',
                'description' => 'Uniforme profissional que aguenta o trabalho duro! Camisa caqui resistente com logo bordado. Para equipas que trabalham no campo, construção ou serviços. Durabilidade e profissionalismo em cada costura!',
                'price' => 2150.00, // SOURCE: PDF - 2,150 Mts
                'min_quantity' => 10,
                'design_hint' => 'Logo bordado no peito. Design profissional para uniformes de trabalho.',
                'is_featured' => false,
                'sort_order' => 67,
                'category_slugs' => ['camisas'],
                'tag_slugs' => ['bordado', 'producao-normal', 'premium', 'uniforme'],
                'colors' => [
                    ['name' => 'Caqui', 'hex_code' => '#C3B091'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                ],
            ],
            [
                'name' => 'Colete Caqui',
                'slug' => 'colete-caqui',
                'description' => 'Proteção e identificação numa coisa só! Colete caqui com bolsos práticos e logo bem visível. Perfeito para equipas de segurança, construção e serviços externos. Porque segurança também precisa de estilo!',
                'price' => 3760.00, // SOURCE: PDF - 3,760 Mts
                'min_quantity' => 10,
                'design_hint' => 'Logo bordado. Design prático com bolsos. Para uniformes de trabalho.',
                'is_featured' => false,
                'sort_order' => 68,
                'category_slugs' => ['coletes'],
                'tag_slugs' => ['bordado', 'producao-normal', 'premium', 'uniforme'],
                'colors' => [
                    ['name' => 'Caqui', 'hex_code' => '#C3B091'],
                ],
                'print_areas' => [
                    ['name' => 'Peito', 'position' => PrintAreaPosition::FRONT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 30, 'max_height_cm' => 35],
                ],
            ],
            [
                'name' => 'Moletom Personalizado',
                'slug' => 'moletom-personalizado',
                'description' => 'Conforto que promove! Moletom personalizado perfeito para eventos, equipas e dias mais frescos. Design que aquece e promove a marca ao mesmo tempo. Porque até o frio pode ser uma oportunidade de marketing!',
                'price' => 1800.00, // MARKET: Hoodie pricing (premium vs t-shirt)
                'min_quantity' => 10,
                'design_hint' => 'Design centralizado no peito ou costas. Área de impressão: 30-35cm largura x 40-45cm altura. Cores vibrantes funcionam bem. Sublimação para designs completos ou serigrafia para logos. Evite áreas de zíper e bolsos.',
                'is_featured' => false,
                'sort_order' => 69,
                'category_slugs' => ['moletom'],
                'tag_slugs' => ['sublimacao', 'serigrafia', 'producao-normal', 'premium', 'ordem-media'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente (Peito)', 'position' => PrintAreaPosition::FRONT_CHEST, 'max_width_cm' => 30, 'max_height_cm' => 35],
                    ['name' => 'Costas (Completo)', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 35, 'max_height_cm' => 50],
                ],
            ],
            [
                'name' => 'Boné Estampado',
                'slug' => 'bone-estampado',
                'description' => 'Protege do sol, promove a marca! Bonés confortáveis com impressão resistente. Perfeitos para eventos ao ar livre, equipas e brindes que realmente são usados. Marketing que anda com pernas (e cabeças)!',
                'price' => 185.00, // SOURCE: PDF - 185 Mts (CORRECTED from 450 Mts for MOQ 25)
                'min_quantity' => 25,
                'design_hint' => 'Logo na frente centralizada (máximo 8cm x 3cm). Design simples funciona melhor. Evite designs muito complexos. Posição: centro da aba frontal, 2-3cm da borda superior.',
                'is_featured' => false,
                'sort_order' => 70,
                'category_slugs' => ['bones'],
                'tag_slugs' => ['serigrafia', 'sublimacao', 'producao-normal', 'economico', 'ordem-media'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Azul', 'hex_code' => '#0000FF'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                    ['name' => 'Verde', 'hex_code' => '#008000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 8, 'max_height_cm' => 3],
                ],
            ],
            [
                'name' => 'Boné Bordado',
                'slug' => 'bone-bordado',
                'description' => 'Versão premium do clássico! Bordado elegante que aguenta sol, chuva e o tempo. Para marcas que não aceitam menos que perfeição. Durabilidade é garantia.',
                'price' => 285.00, // SOURCE: PDF - 285 Mts
                'min_quantity' => 25,
                'design_hint' => 'Logo na frente centralizada (máximo 8cm x 3cm). Bordado para durabilidade e aparência premium. Design simples funciona melhor.',
                'is_featured' => false,
                'sort_order' => 71,
                'category_slugs' => ['bones'],
                'tag_slugs' => ['bordado', 'producao-normal', 'medio', 'ordem-media', 'premium'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                    ['name' => 'Azul', 'hex_code' => '#0000FF'],
                    ['name' => 'Vermelho', 'hex_code' => '#FF0000'],
                    ['name' => 'Verde', 'hex_code' => '#008000'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 8, 'max_height_cm' => 3],
                ],
            ],
            [
                'name' => 'Jaqueta Personalizada',
                'slug' => 'jaqueta-personalizada',
                'description' => 'Estilo que aquece e impressiona! Jaquetas personalizadas perfeitas para equipas, eventos e dias mais frescos. Design que protege do frio e promove a marca. Porque até o inverno pode ser uma oportunidade de marketing!',
                'price' => 2200.00, // MARKET: Jacket pricing
                'min_quantity' => 10,
                'design_hint' => 'Logo no peito esquerdo (10cm x 10cm) ou design grande nas costas (35cm x 45cm). Cores contrastantes com o tecido. Sublimação para designs completos. Evite áreas de zíper e bolsos. Posição peito: 15-20cm abaixo do ombro.',
                'is_featured' => false,
                'sort_order' => 72,
                'category_slugs' => ['jaquetas'],
                'tag_slugs' => ['bordado', 'sublimacao', 'serigrafia', 'producao-normal', 'premium', 'ordem-media'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul Marinho', 'hex_code' => '#001F3F'],
                    ['name' => 'Cinza', 'hex_code' => '#808080'],
                    ['name' => 'Verde Escuro', 'hex_code' => '#006400'],
                ],
                'print_areas' => [
                    ['name' => 'Peito Esquerdo', 'position' => PrintAreaPosition::LEFT_CHEST, 'max_width_cm' => 10, 'max_height_cm' => 10],
                    ['name' => 'Costas', 'position' => PrintAreaPosition::BACK_FULL, 'max_width_cm' => 35, 'max_height_cm' => 45],
                ],
            ],
            [
                'name' => 'Guarda-chuvas',
                'slug' => 'guarda-chuvas',
                'description' => 'Proteção com estilo! Guarda-chuvas personalizados que protegem da chuva e promovem a marca. Perfeitos para eventos, brindes corporativos e dias chuvosos. Porque até a chuva pode ser uma oportunidade de marketing!',
                'price' => 450.00, // MARKET: Umbrella pricing
                'min_quantity' => 25,
                'design_hint' => 'Logo no topo ou painéis. Cores vibrantes. Considerar visibilidade quando aberto.',
                'is_featured' => false,
                'sort_order' => 73,
                'category_slugs' => ['guarda-chuvas'],
                'tag_slugs' => ['importacao', 'sublimacao', 'producao-normal', 'medio'],
                'colors' => [
                    ['name' => 'Preto', 'hex_code' => '#000000'],
                    ['name' => 'Azul', 'hex_code' => '#0000FF'],
                ],
                'print_areas' => [
                    ['name' => 'Painéis', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 30.0, 'max_height_cm' => 30.0],
                ],
            ],
            [
                'name' => 'Distintivos Magnéticos',
                'slug' => 'distintivos-magneticos',
                'description' => 'Identifique sem furar! Distintivos magnéticos que colam na roupa sem danificar o tecido. Perfeitos para eventos, conferências e identificação de equipas. Prático, elegante e sem complicações!',
                'price' => 65.00, // MARKET: Name badge pricing
                'min_quantity' => 25,
                'design_hint' => 'Nome e logo claros. Texto legível (mínimo 12pt). Cores corporativas.',
                'is_featured' => false,
                'sort_order' => 74,
                'category_slugs' => ['distintivos-magneticos'],
                'tag_slugs' => ['impressao-digital', 'producao-normal', 'economico'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Frente', 'position' => PrintAreaPosition::FRONT, 'max_width_cm' => 8.0, 'max_height_cm' => 5.0],
                ],
            ],
            
            // ===================================================================
            // CANVAS / DÉCOR ITEMS
            // ===================================================================
            
            [
                'name' => 'Quadro Canvas',
                'slug' => 'quadro-canvas',
                'description' => 'Arte que decora e promove! Quadros em canvas de alta qualidade para escritórios, lojas e espaços comerciais. Transforme paredes vazias em pontos focais de branding. Do pequeno ao imponente, temos o tamanho que precisa.',
                'price' => 1000.00, // Base price (smallest size)
                'min_quantity' => 1,
                'pricing_type' => PricingType::FIXED,
                'has_sizes' => true,
                'design_hint' => 'Imagem de alta resolução (300dpi mínimo). Considere margem para moldura. Cores vibrantes funcionam bem.',
                'is_featured' => false,
                'sort_order' => 75,
                'category_slugs' => ['quadros-canvas'],
                'tag_slugs' => ['impressao-digital', 'producao-normal', 'premium', 'decoracao'],
                'colors' => [
                    ['name' => 'Branco', 'hex_code' => '#FFFFFF'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FULL_POSTER, 'max_width_cm' => 70.0, 'max_height_cm' => 100.0],
                ],
                'sizes' => [
                    ['name' => '70x100cm', 'width_cm' => 70.0, 'height_cm' => 100.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 4900.00],
                    ['name' => '60x80cm', 'width_cm' => 60.0, 'height_cm' => 80.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 3300.00],
                    ['name' => '45x60cm', 'width_cm' => 45.0, 'height_cm' => 60.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 2100.00],
                    ['name' => '40x40cm', 'width_cm' => 40.0, 'height_cm' => 40.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1300.00],
                    ['name' => '42x30cm', 'width_cm' => 42.0, 'height_cm' => 30.0, 'is_predefined' => true, 'is_custom' => false, 'fixed_price' => 1000.00],
                ],
            ],
            [
                'name' => 'Quadro de Pedra',
                'slug' => 'quadro-de-pedra',
                'description' => 'Elegância natural! Sublimação em pedra para um toque único e sofisticado. Perfeito para presentes corporativos especiais ou decoração diferenciada. A natureza e a tecnologia em harmonia.',
                'price' => 975.00, // SOURCE: PDF - 975 Mts
                'min_quantity' => 1,
                'design_hint' => 'Imagem de alta resolução. Efeito natural da pedra adiciona textura. Design elegante.',
                'is_featured' => false,
                'sort_order' => 80,
                'category_slugs' => ['quadros'],
                'tag_slugs' => ['sublimacao', 'producao-normal', 'medio', 'decoracao', 'premium'],
                'colors' => [
                    ['name' => 'Natural', 'hex_code' => '#D2B48C'],
                ],
                'print_areas' => [
                    ['name' => 'Área Completa', 'position' => PrintAreaPosition::FULL_POSTER, 'max_width_cm' => 20.0, 'max_height_cm' => 15.0],
                ],
            ],
        ];

        foreach ($products as $productData) {
            $colors = $productData['colors'] ?? [];
            $printAreas = $productData['print_areas'] ?? [];
            $sizes = $productData['sizes'] ?? [];
            $sizeRestrictions = $productData['size_restrictions'] ?? [];
            $categorySlugs = $productData['category_slugs'] ?? [];
            $tagSlugs = $productData['tag_slugs'] ?? [];
            unset($productData['colors'], $productData['print_areas'], $productData['sizes'], $productData['size_restrictions'], $productData['category_slugs'], $productData['tag_slugs']);

            // Check if product already exists by slug
            $product = Product::where('slug', $productData['slug'])->first();
            if (!$product) {
                $productData['uuid'] = Str::uuid();
                $productData['status'] = ProductStatus::ACTIVE;
                $product = Product::create($productData);

                // Create colors for new product
                foreach ($colors as $index => $colorData) {
                    $colorData['product_id'] = $product->id;
                    $colorData['sort_order'] = $index;
                    $colorData['is_active'] = true;
                    ProductColor::create($colorData);
                }

                // Create print areas for new product
                foreach ($printAreas as $index => $areaData) {
                    $areaData['product_id'] = $product->id;
                    $areaData['sort_order'] = $index;
                    $areaData['is_active'] = true;
                    $areaData['additional_price'] = 0;
                    ProductPrintArea::create($areaData);
                }

                // Create sizes for new product
                foreach ($sizes as $index => $sizeData) {
                    $sizeData['product_id'] = $product->id;
                    $sizeData['sort_order'] = $index;
                    $sizeData['is_active'] = true;
                    ProductSize::create($sizeData);
                }

                // Create size restrictions for new product
                if (!empty($sizeRestrictions)) {
                    $sizeRestrictions['product_id'] = $product->id;
                    ProductSizeRestriction::create($sizeRestrictions);
                }
            } else {
                // Update existing product
                $productData['status'] = ProductStatus::ACTIVE;
                $product->update($productData);

                // Update sizes if provided
                if (!empty($sizes)) {
                    // Delete existing sizes and create new ones
                    $product->sizes()->delete();
                    foreach ($sizes as $index => $sizeData) {
                        $sizeData['product_id'] = $product->id;
                        $sizeData['sort_order'] = $index;
                        $sizeData['is_active'] = true;
                        ProductSize::create($sizeData);
                    }
                }

                // Update size restrictions if provided
                if (!empty($sizeRestrictions)) {
                    $product->sizeRestrictions()->delete();
                    $sizeRestrictions['product_id'] = $product->id;
                    ProductSizeRestriction::create($sizeRestrictions);
                }
            }

            // Attach categories
            if (!empty($categorySlugs)) {
                $categories = Category::whereIn('slug', $categorySlugs)->pluck('id');
                $product->categories()->sync($categories);
            }

            // Attach tags
            if (!empty($tagSlugs)) {
                $tags = Tag::whereIn('slug', $tagSlugs)->pluck('id');
                $product->tags()->sync($tags);
            }
        }
    }
}