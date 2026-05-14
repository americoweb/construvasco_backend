<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\SuggestionServiceInterface;
use App\DTOs\AI\SuggestionRequest;
use App\DTOs\AI\SuggestionResponse;
use App\DTOs\AI\ProductSuggestion;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Exception;

class SuggestionService implements SuggestionServiceInterface
{
    public function __construct(
        protected GeminiService $geminiService,
        protected FallbackSuggestionService $fallbackService
    ) {}

    public function getSmartSuggestions(SuggestionRequest $request): array
    {
        // Check cache first
        $cacheKey = $this->generateCacheKey($request);
        
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            if (!$this->geminiService->isConfigured()) {
                Log::warning('Gemini not configured, using fallback');
                return $this->fallbackService->getSuggestions($request);
            }

            $suggestions = $this->generateSuggestions($request);
            
            // Generate mockups for each suggestion
            $suggestions = $this->enrichWithMockups($suggestions, $request);
            
            // Cache for 1 hour
            Cache::put($cacheKey, $suggestions, 3600);

            return $suggestions;

        } catch (Exception $e) {
            Log::error('AI suggestion generation failed', [
                'error' => $e->getMessage(),
                'goal' => $request->goal,
                'budget' => $request->budget,
            ]);

            return $this->fallbackService->getSuggestions($request);
        }
    }

    protected function generateSuggestions(SuggestionRequest $request): array
    {
        $products = $this->getAvailableProducts();
        $prompt = $this->buildSuggestionPrompt($request, $products);
        
        $schema = [
            'suggestions' => [
                [
                    'type' => 'string (single or bundle)',
                    'title' => 'string',
                    'products' => [
                        [
                            'name' => 'string (exact product name from catalog)',
                            'design_prompt' => 'string',
                            'theme' => 'string',
                            'text_to_print' => 'string',
                            'color_style' => 'string',
                            'price' => 'number',
                            'quantity' => 'number'
                        ]
                    ],
                    'estimated_total' => 'number',
                    'cta' => 'string (call to action in Portuguese)'
                ]
            ]
        ];

        $response = $this->geminiService->generateStructuredResponse($prompt, $schema, [
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ]);

        return $this->parseSuggestionResponse($response, $products);
    }

    protected function buildSuggestionPrompt(SuggestionRequest $request, $products): string
    {
        $productCatalog = $this->formatProductCatalog($products);
        
        $prompt = <<<PROMPT
Você é um consultor especialista em brindes corporativos e produtos promocionais em Moçambique.

OBJETIVO DO CLIENTE: {$request->goal}
ORÇAMENTO: {$request->budget} MT (Meticais)

CATÁLOGO DE PRODUTOS DISPONÍVEIS:
{$productCatalog}

TAREFA:
Analise o objetivo e orçamento do cliente e sugira os melhores produtos do catálogo.

REGRAS:
1. Respeite ESTRITAMENTE o orçamento - total não pode exceder {$request->budget} MT
2. Use APENAS produtos do catálogo fornecido
3. Considere as quantidades mínimas de cada produto
4. O nome do produto DEVE corresponder EXATAMENTE ao catálogo
5. Gere prompts de design criativos e relevantes para o objetivo
6. Inclua texto sugerido para impressão quando apropriado

SUGESTÕES NECESSÁRIAS:
- 2-3 sugestões de produtos individuais (type: "single")
PROMPT;

        if ($request->includeBundles) {
            $prompt .= <<<PROMPT

- 1-2 sugestões de pacotes/bundles (type: "bundle") com 2-4 produtos complementares
PROMPT;
        }

        $prompt .= <<<PROMPT


FORMATO DO CTA (Call to Action):
- Deve ser em português de Moçambique
- Frases motivadoras como "Impressione seus convidados!" ou "Destaque sua marca!"
- Máximo 50 caracteres

Gere sugestões criativas que atendam ao objetivo do cliente dentro do orçamento.
PROMPT;

        if ($request->hasLogo()) {
            $prompt .= "\n\nNOTA: O cliente forneceu um logo que deve ser incorporado nos designs.";
        }

        return $prompt;
    }

    protected function formatProductCatalog($products): string
    {
        $catalog = "";
        
        foreach ($products as $product) {
            $catalog .= "- {$product->name}\n";
            $catalog .= "  Preço: {$product->price} MT\n";
            $catalog .= "  Quantidade Mínima: {$product->min_quantity}\n";
            $catalog .= "  Cores: " . $product->colors->pluck('name')->implode(', ') . "\n";
            $catalog .= "  Áreas de Impressão: " . $product->printAreas->pluck('name')->implode(', ') . "\n";
            if ($product->design_hint) {
                $catalog .= "  Dica: {$product->design_hint}\n";
            }
            $catalog .= "\n";
        }

        return $catalog;
    }

    protected function parseSuggestionResponse(array $response, $products): array
    {
        $suggestions = [];
        $productMap = $products->keyBy('name');

        foreach ($response['suggestions'] ?? [] as $suggestionData) {
            $suggestionProducts = [];
            $totalPrice = 0;

            foreach ($suggestionData['products'] ?? [] as $productData) {
                $productName = $productData['name'];
                $dbProduct = $productMap->get($productName);
                
                if (!$dbProduct) {
                    Log::warning('Product not found in catalog', ['name' => $productName]);
                    continue;
                }

                $quantity = max($productData['quantity'] ?? 1, $dbProduct->min_quantity);
                $price = $dbProduct->price;
                
                $suggestionProducts[] = new ProductSuggestion(
                    name: $productName,
                    designPrompt: $productData['design_prompt'] ?? '',
                    theme: $productData['theme'] ?? '',
                    textToPrint: $productData['text_to_print'] ?? '',
                    colorStyle: $productData['color_style'] ?? '',
                    price: $price,
                    quantity: $quantity,
                    productId: $dbProduct->id
                );

                $totalPrice += $price * $quantity;
            }

            if (!empty($suggestionProducts)) {
                $suggestions[] = new SuggestionResponse(
                    type: $suggestionData['type'] ?? 'single',
                    title: $suggestionData['title'] ?? 'Sugestão',
                    products: $suggestionProducts,
                    estimatedTotal: $totalPrice,
                    cta: $suggestionData['cta'] ?? 'Personalize agora!'
                );
            }
        }

        return $suggestions;
    }

    protected function enrichWithMockups(array $suggestions, SuggestionRequest $request): array
    {
        foreach ($suggestions as $suggestion) {
            foreach ($suggestion->products as $product) {
                try {
                    $mockupUrl = $this->generateMockup($product->productId, [
                        'design_prompt' => $product->designPrompt,
                        'logo_base64' => $request->logoBase64,
                        'logo_mime_type' => $request->logoMimeType,
                        'reference_image_base64' => $request->referenceImageBase64,
                        'reference_image_mime_type' => $request->referenceImageMimeType,
                    ]);
                    
                    $product->mockupUrl = $mockupUrl;
                } catch (Exception $e) {
                    Log::warning('Failed to generate mockup', [
                        'product' => $product->name,
                        'error' => $e->getMessage(),
                    ]);
                    // Use fallback image
                    $product->mockupUrl = $this->getFallbackMockupUrl($product->productId);
                }
            }
        }

        return $suggestions;
    }

    public function generateMockup(int $productId, array $designData): string
    {
        $product = Product::with(['colors', 'printAreas'])->find($productId);
        
        if (!$product) {
            Log::error('Product not found', ['product_id' => $productId]);
            throw new Exception('Product not found');
        }

        // Get base image for prompting - prefer base_image_url, fallback to image_url
        // IMPORTANT: Use base_image_url (clean base image) for prompting, but fallback to image_url if base doesn't exist
        $baseImagePath = null;
        $imageSource = null;
        
        // Try base_image_url first
        if (!empty($product->base_image_url)) {
            $baseImagePath = $product->base_image_url;
            $imageSource = 'base_image_url';
        } 
        // Fallback to image_url if base_image_url doesn't exist
        elseif (!empty($product->image_url)) {
            $baseImagePath = $product->image_url;
            $imageSource = 'image_url';
            Log::info('Using image_url as fallback for base_image_url', ['product_id' => $productId]);
        }
        
        if (empty($baseImagePath)) {
            Log::warning('No image found for mockup generation', ['product_id' => $productId]);
            return $this->getFallbackMockupUrl($productId);
        }

        // Convert relative path to absolute path if needed
        if (!str_starts_with($baseImagePath, '/') && !str_starts_with($baseImagePath, 'http')) {
            $baseImagePath = storage_path('app/public/' . ltrim($baseImagePath, '/'));
        }

        // If base_image_url file doesn't exist, try falling back to image_url
        if (!file_exists($baseImagePath) && $imageSource === 'base_image_url' && !empty($product->image_url)) {
            Log::warning('Base image file not found, falling back to image_url', [
                'product_id' => $productId,
                'base_path' => $baseImagePath
            ]);
            
            // Try image_url as fallback
            $fallbackPath = $product->image_url;
            if (!str_starts_with($fallbackPath, '/') && !str_starts_with($fallbackPath, 'http')) {
                $fallbackPath = storage_path('app/public/' . ltrim($fallbackPath, '/'));
            }
            
            if (file_exists($fallbackPath)) {
                $baseImagePath = $fallbackPath;
                $imageSource = 'image_url';
                Log::info('Successfully using image_url as fallback', ['product_id' => $productId]);
            } else {
                Log::error('Both base_image_url and image_url files not found', [
                    'product_id' => $productId,
                    'base_path' => $baseImagePath,
                    'fallback_path' => $fallbackPath
                ]);
                return $this->getFallbackMockupUrl($productId);
            }
        } elseif (!file_exists($baseImagePath)) {
            Log::error('Image file not found', [
                'product_id' => $productId,
                'path' => $baseImagePath,
                'source' => $imageSource
            ]);
            return $this->getFallbackMockupUrl($productId);
        }

        try {
            // Convert base image to base64
            $baseImageContent = file_get_contents($baseImagePath);
            if ($baseImageContent === false) {
                throw new Exception('Failed to read base image file');
            }
            
            $baseImageBase64 = base64_encode($baseImageContent);
            $baseMimeType = mime_content_type($baseImagePath) ?: 'image/png';

            // Get selected color if color_id is provided, otherwise use first color
            $selectedColor = null;
            if (!empty($designData['color_id'])) {
                $selectedColor = $product->colors->where('id', $designData['color_id'])->first();
            }
            $productColor = $selectedColor?->name ?? $product->colors->first()?->name ?? 'branco';

            // Handle empty/null design prompt when logo or reference is provided
            $designPrompt = $designData['design_prompt'] ?? '';
            $hasLogo = !empty($designData['logo_base64']);
            $hasReference = !empty($designData['reference_image_base64']);
            
            // If prompt is empty but logo/reference exists, provide a default prompt
            if (empty(trim($designPrompt))) {
                if ($hasLogo && $hasReference) {
                    $designPrompt = 'Aplique o logotipo fornecido no produto, usando a imagem de referência como guia para o layout e estilo.';
                } elseif ($hasLogo) {
                    $designPrompt = 'Aplique o logotipo fornecido no produto de forma profissional e bem posicionada.';
                } elseif ($hasReference) {
                    $designPrompt = 'Use a imagem de referência como guia para criar o design no produto, replicando o estilo e layout.';
                } else {
                    // This shouldn't happen due to validation, but provide a fallback
                    $designPrompt = 'Crie um design personalizado para este produto.';
                }
            }

            $mockupResponse = $this->geminiService->generateMockupWithVision(
                $baseImageBase64,
                $baseMimeType,
                $designPrompt,
                [
                    'product_name' => $product->name,
                    'product_color' => $productColor,
                    'print_area' => $product->printAreas->first()?->name ?? 'frente',
                    'design_hint' => $product->design_hint ?? '',
                    'logo_base64' => $designData['logo_base64'] ?? null,
                    'logo_mime_type' => $designData['logo_mime_type'] ?? null,
                    'reference_image_base64' => $designData['reference_image_base64'] ?? null,
                    'reference_image_mime_type' => $designData['reference_image_mime_type'] ?? null,
                ]
            );

            // If we got a base64 image back, save it and return URL
            if ($this->isBase64Image($mockupResponse)) {
                return $this->saveMockupImage($mockupResponse, $productId);
            }

            Log::warning('Invalid base64 image response', ['product_id' => $productId]);
            return $this->getFallbackMockupUrl($productId);

        } catch (Exception $e) {
            Log::error('Mockup generation failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return $this->getFallbackMockupUrl($productId);
        }
    }

    public function generateHouseRender(int $productId, array $designData): string
    {
        $basePrompt = trim((string) ($designData['design_prompt'] ?? ''));
        $designData['design_prompt'] = "Crie um render arquitetônico realista de uma casa em Moçambique, com foco em fachada e volumetria. {$basePrompt}";

        return $this->generateMockup($productId, $designData);
    }

    public function generateFloorPlan(int $productId, array $designData, string $houseImageUrl = ''): string
    {
        $basePrompt = trim((string) ($designData['design_prompt'] ?? ''));
        $designData['design_prompt'] = "Crie uma planta baixa técnica correspondente à casa descrita, com divisão de ambientes, circulação e proporções realistas. {$basePrompt}";

        if (!empty($houseImageUrl) && empty($designData['reference_image_base64'])) {
            $houseImageContent = @file_get_contents($houseImageUrl);
            if ($houseImageContent !== false) {
                $designData['reference_image_base64'] = base64_encode($houseImageContent);
                $designData['reference_image_mime_type'] = 'image/png';
            }
        }

        return $this->generateMockup($productId, $designData);
    }

    public function refineDesign(string $currentPrompt, string $feedback): string
    {
        $prompt = <<<REFINE_PROMPT
Você é um assistente de design. O usuário criou um design com o seguinte prompt:

PROMPT ATUAL: {$currentPrompt}

FEEDBACK DO USUÁRIO: {$feedback}

Baseado no feedback, gere um novo prompt de design melhorado que incorpore as mudanças solicitadas.
Mantenha os elementos bons do design original e aplique as melhorias.

Responda APENAS com o novo prompt de design, sem explicações adicionais.
REFINE_PROMPT;

        return $this->geminiService->generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 500,
        ]);
    }

    protected function getAvailableProducts()
    {
        return Product::with(['colors', 'printAreas'])
            ->active()
            ->orderBy('price')
            ->get();
    }

    protected function generateCacheKey(SuggestionRequest $request): string
    {
        return 'ai_suggestions_' . md5(serialize([
            'goal' => $request->goal,
            'budget' => $request->budget,
            'has_logo' => $request->hasLogo(),
            'has_reference' => $request->hasReferenceImage(),
        ]));
    }

    protected function isBase64Image(string $data): bool
    {
        if (strlen($data) < 100) {
            return false;
        }
        
        // Handle data URL format (data:image/png;base64,...)
        if (str_starts_with($data, 'data:image')) {
            // Extract base64 part after the comma
            $base64Part = explode(',', $data)[1] ?? '';
            return base64_decode($base64Part, true) !== false;
        }
        
        // Handle plain base64 string
        return base64_decode($data, true) !== false;
    }

    protected function saveMockupImage(string $base64Data, int $productId): string
    {
        $filename = "mockup_{$productId}_" . time() . '_' . uniqid() . '.png';
        $relativePath = "mockups/{$filename}";
        
        // Extract base64 data if it's in data URL format (data:image/png;base64,...)
        $imageData = $base64Data;
        if (str_starts_with($base64Data, 'data:image')) {
            $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64Data);
        }

        $decoded = base64_decode($imageData, true);
        if ($decoded === false) {
            Log::error('Failed to decode base64 image', ['product_id' => $productId]);
            throw new Exception('Failed to decode base64 image data');
        }

        // Save using Storage facade
        Storage::disk('public')->put($relativePath, $decoded);

        // Return relative path (same format as product images)
        // The frontend/API will construct the full URL
        return $relativePath;
    }

    protected function getFallbackMockupUrl(int $productId): string
    {
        $product = Product::find($productId);
        
        if ($product && $product->image_url) {
            return $product->image_url;
        }

        return "https://via.placeholder.com/640x480/cccccc/666666?text=Mockup+Preview";
    }
}
