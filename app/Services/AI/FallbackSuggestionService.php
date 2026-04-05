<?php

namespace App\Services\AI;

use App\DTOs\AI\SuggestionRequest;
use App\DTOs\AI\SuggestionResponse;
use App\DTOs\AI\ProductSuggestion;
use App\Models\Product\Product;

class FallbackSuggestionService
{
    public function getSuggestions(SuggestionRequest $request): array
    {
        $products = Product::with(['colors', 'printAreas'])
            ->active()
            ->orderBy('price')
            ->get();

        if ($products->isEmpty()) {
            return $this->getDefaultSuggestions($request);
        }

        $suggestions = [];

        // Add individual product suggestions
        $affordableProducts = $products->filter(function ($product) use ($request) {
            return ($product->price * $product->min_quantity) <= $request->budget;
        })->take(3);

        foreach ($affordableProducts as $product) {
            $suggestions[] = $this->createSingleSuggestion($product, $request);
        }

        // Add bundle suggestion if budget allows
        if ($request->includeBundles && $request->budget >= 2000) {
            $bundle = $this->createBundleSuggestion($products, $request);
            if ($bundle) {
                $suggestions[] = $bundle;
            }
        }

        return $suggestions;
    }

    protected function createSingleSuggestion(Product $product, SuggestionRequest $request): SuggestionResponse
    {
        $quantity = $product->min_quantity;
        $total = $product->price * $quantity;

        // Adjust quantity if budget allows more
        while (($quantity + $product->min_quantity) * $product->price <= $request->budget) {
            $quantity += $product->min_quantity;
            $total = $product->price * $quantity;
        }

        $productSuggestion = new ProductSuggestion(
            name: $product->name,
            designPrompt: $this->generateDefaultPrompt($product, $request->goal),
            theme: $this->extractTheme($request->goal),
            textToPrint: $this->generateDefaultText($request->goal),
            colorStyle: 'Moderno e Profissional',
            price: $product->price,
            quantity: $quantity,
            mockupUrl: $product->image_url,
            productId: $product->id
        );

        return new SuggestionResponse(
            type: 'single',
            title: $product->name,
            products: [$productSuggestion],
            estimatedTotal: $total,
            cta: $this->generateCTA($product->name)
        );
    }

    protected function createBundleSuggestion($products, SuggestionRequest $request): ?SuggestionResponse
    {
        $bundleProducts = [];
        $remainingBudget = $request->budget;
        $totalPrice = 0;

        // Try to fit 2-3 products in budget
        $selectedProducts = $products->filter(function ($product) use (&$remainingBudget) {
            $productCost = $product->price * $product->min_quantity;
            if ($productCost <= $remainingBudget) {
                $remainingBudget -= $productCost;
                return true;
            }
            return false;
        })->take(3);

        if ($selectedProducts->count() < 2) {
            return null;
        }

        foreach ($selectedProducts as $product) {
            $bundleProducts[] = new ProductSuggestion(
                name: $product->name,
                designPrompt: $this->generateDefaultPrompt($product, $request->goal),
                theme: $this->extractTheme($request->goal),
                textToPrint: $this->generateDefaultText($request->goal),
                colorStyle: 'Moderno e Profissional',
                price: $product->price,
                quantity: $product->min_quantity,
                mockupUrl: $product->image_url,
                productId: $product->id
            );

            $totalPrice += $product->price * $product->min_quantity;
        }

        return new SuggestionResponse(
            type: 'bundle',
            title: 'Pacote Promocional',
            products: $bundleProducts,
            estimatedTotal: $totalPrice,
            cta: 'Kit completo para sua marca!',
            bundleTheme: 'Branding Corporativo'
        );
    }

    protected function generateDefaultPrompt(Product $product, string $goal): string
    {
        $prompts = [
            'Camiseta' => "Design moderno e profissional para {$goal}, com elementos gráficos limpos e logo bem posicionado no centro do peito.",
            'Caneca' => "Design envolvente para caneca relacionado a {$goal}, com padrão que funciona bem ao redor da caneca.",
            'Cartão de Visita' => "Layout profissional para cartão de visita focado em {$goal}, com espaço para informações de contacto.",
            'Pôster' => "Design impactante para pôster sobre {$goal}, com tipografia grande e elementos visuais chamativos.",
            'Camisa Polo' => "Logo discreto e profissional para polo relacionado a {$goal}, ideal para uso corporativo.",
            'Caderno' => "Capa criativa para caderno com tema {$goal}, design que inspire e motive.",
            'Garrafa' => "Design vertical para garrafa sobre {$goal}, padrões que se integram bem à forma cilíndrica.",
            'Sacola' => "Arte grande e visível para sacola ecológica sobre {$goal}, design sustentável e moderno.",
        ];

        foreach ($prompts as $key => $prompt) {
            if (stripos($product->name, $key) !== false) {
                return $prompt;
            }
        }

        return "Design profissional e moderno para {$product->name} relacionado a {$goal}.";
    }

    protected function extractTheme(string $goal): string
    {
        $keywords = [
            'empresa' => 'Corporativo',
            'evento' => 'Evento Especial',
            'conferência' => 'Profissional',
            'workshop' => 'Educacional',
            'casamento' => 'Celebração',
            'aniversário' => 'Festivo',
            'lançamento' => 'Inovação',
            'promoção' => 'Marketing',
        ];

        foreach ($keywords as $keyword => $theme) {
            if (stripos($goal, $keyword) !== false) {
                return $theme;
            }
        }

        return 'Personalizado';
    }

    protected function generateDefaultText(string $goal): string
    {
        // Extract potential text from goal
        if (preg_match('/para\s+(.+?)(?:\s+de|\s+em|\s*$)/i', $goal, $matches)) {
            return ucwords(trim($matches[1]));
        }

        return '';
    }

    protected function generateCTA(string $productName): string
    {
        $ctas = [
            'Destaque sua marca!',
            'Impressione seus convidados!',
            'Personalize agora!',
            'Qualidade garantida!',
            'Faça a diferença!',
            'Seja único!',
        ];

        return $ctas[array_rand($ctas)];
    }

    protected function getDefaultSuggestions(SuggestionRequest $request): array
    {
        // Return empty array if no products in database
        return [];
    }
}
