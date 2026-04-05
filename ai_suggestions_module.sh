#!/bin/bash

# iOPS Amazing Brindes - AI Suggestions Module Setup Script
# Gemini AI integration for smart product recommendations
# Run this from the Laravel root directory (where vendor folder exists)

echo "🤖 Setting up Amazing Brindes AI Suggestions Module..."

# Check if we're in the correct directory
if [ ! -d "vendor" ]; then
    echo "❌ Error: Please run this script from the Laravel root directory (where vendor folder exists)"
    exit 1
fi

# Create directory structure
echo "📁 Creating directory structure..."

mkdir -p app/Http/Controllers/AI
mkdir -p app/Services/AI
mkdir -p app/Services/AI/Contracts
mkdir -p app/Services/AI/Fallbacks
mkdir -p app/Http/Requests/AI
mkdir -p app/Http/Resources/AI
mkdir -p app/Jobs/AI
mkdir -p app/Events/AI
mkdir -p app/DTOs/AI

echo "✅ Directory structure created!"

# ============================================
# DTOs (Data Transfer Objects)
# ============================================
echo "📦 Creating DTOs..."

cat > app/DTOs/AI/SuggestionRequest.php << 'EOF'
<?php

namespace App\DTOs\AI;

class SuggestionRequest
{
    public function __construct(
        public string $goal,
        public float $budget,
        public ?string $logoBase64 = null,
        public ?string $logoMimeType = null,
        public ?string $referenceImageBase64 = null,
        public ?string $referenceImageMimeType = null,
        public int $maxSuggestions = 5,
        public bool $includeBundles = true
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            goal: $data['goal'],
            budget: (float) $data['budget'],
            logoBase64: $data['logo_base64'] ?? null,
            logoMimeType: $data['logo_mime_type'] ?? null,
            referenceImageBase64: $data['reference_image_base64'] ?? null,
            referenceImageMimeType: $data['reference_image_mime_type'] ?? null,
            maxSuggestions: $data['max_suggestions'] ?? 5,
            includeBundles: $data['include_bundles'] ?? true
        );
    }

    public function hasLogo(): bool
    {
        return !empty($this->logoBase64);
    }

    public function hasReferenceImage(): bool
    {
        return !empty($this->referenceImageBase64);
    }
}
EOF

cat > app/DTOs/AI/ProductSuggestion.php << 'EOF'
<?php

namespace App\DTOs\AI;

class ProductSuggestion
{
    public function __construct(
        public string $name,
        public string $designPrompt,
        public string $theme,
        public string $textToPrint,
        public string $colorStyle,
        public float $price,
        public int $quantity,
        public ?string $mockupUrl = null,
        public ?int $productId = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            designPrompt: $data['design_prompt'] ?? $data['designPrompt'] ?? '',
            theme: $data['theme'] ?? '',
            textToPrint: $data['text_to_print'] ?? $data['textToPrint'] ?? '',
            colorStyle: $data['color_style'] ?? $data['colorStyle'] ?? '',
            price: (float) ($data['price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1),
            mockupUrl: $data['mockup_url'] ?? $data['mockupUrl'] ?? null,
            productId: $data['product_id'] ?? $data['productId'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'design_prompt' => $this->designPrompt,
            'theme' => $this->theme,
            'text_to_print' => $this->textToPrint,
            'color_style' => $this->colorStyle,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'mockup_url' => $this->mockupUrl,
            'product_id' => $this->productId,
        ];
    }

    public function getTotalPrice(): float
    {
        return $this->price * $this->quantity;
    }
}
EOF

cat > app/DTOs/AI/SuggestionResponse.php << 'EOF'
<?php

namespace App\DTOs\AI;

class SuggestionResponse
{
    public function __construct(
        public string $type, // 'single' or 'bundle'
        public string $title,
        public array $products, // Array of ProductSuggestion
        public float $estimatedTotal,
        public string $cta,
        public ?string $bundleTheme = null
    ) {}

    public static function fromArray(array $data): self
    {
        $products = array_map(
            fn($p) => ProductSuggestion::fromArray($p),
            $data['products'] ?? []
        );

        return new self(
            type: $data['type'] ?? 'single',
            title: $data['title'] ?? '',
            products: $products,
            estimatedTotal: (float) ($data['estimated_total'] ?? $data['estimatedTotal'] ?? 0),
            cta: $data['cta'] ?? '',
            bundleTheme: $data['bundle_theme'] ?? $data['bundleTheme'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'products' => array_map(fn($p) => $p->toArray(), $this->products),
            'estimated_total' => $this->estimatedTotal,
            'cta' => $this->cta,
            'bundle_theme' => $this->bundleTheme,
        ];
    }

    public function getProductCount(): int
    {
        return count($this->products);
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }
}
EOF

# ============================================
# CONTRACTS (INTERFACES)
# ============================================
echo "📋 Creating Contracts..."

cat > app/Services/AI/Contracts/AIServiceInterface.php << 'EOF'
<?php

namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    public function generateText(string $prompt, array $options = []): string;
    
    public function generateImage(string $prompt, array $options = []): string;
    
    public function generateStructuredResponse(string $prompt, array $schema, array $options = []): array;
}
EOF

cat > app/Services/AI/Contracts/SuggestionServiceInterface.php << 'EOF'
<?php

namespace App\Services\AI\Contracts;

use App\DTOs\AI\SuggestionRequest;

interface SuggestionServiceInterface
{
    public function getSmartSuggestions(SuggestionRequest $request): array;
    
    public function generateMockup(int $productId, array $designData): string;
    
    public function refineDesign(string $currentPrompt, string $feedback): string;
}
EOF

# ============================================
# GEMINI SERVICE
# ============================================
echo "🧠 Creating Gemini AI Service..."

cat > app/Services/AI/GeminiService.php << 'EOF'
<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiService implements AIServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected string $visionModel;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->model = config('ai.gemini.model', 'gemini-1.5-flash');
        $this->visionModel = config('ai.gemini.vision_model', 'gemini-1.5-flash');
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.7,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                'topP' => $options['top_p'] ?? 0.9,
            ]
        ];

        return $this->makeRequest($url, $payload);
    }

    public function generateImage(string $prompt, array $options = []): string
    {
        // Gemini doesn't generate images directly
        // This method is for generating mockups with vision model
        throw new Exception('Use generateMockupWithVision for image generation');
    }

    public function generateStructuredResponse(string $prompt, array $schema, array $options = []): array
    {
        $structuredPrompt = $this->buildStructuredPrompt($prompt, $schema);
        
        $response = $this->generateText($structuredPrompt, array_merge($options, [
            'temperature' => 0.3, // Lower temperature for structured output
        ]));

        return $this->parseJsonResponse($response);
    }

    public function generateMockupWithVision(
        string $baseImageBase64,
        string $baseMimeType,
        string $designPrompt,
        array $options = []
    ): string {
        $url = "{$this->baseUrl}/models/{$this->visionModel}:generateContent";

        $parts = [
            [
                'inline_data' => [
                    'mime_type' => $baseMimeType,
                    'data' => $baseImageBase64
                ]
            ],
            [
                'text' => $this->buildMockupPrompt($designPrompt, $options)
            ]
        ];

        // Add logo if provided
        if (!empty($options['logo_base64'])) {
            array_unshift($parts, [
                'inline_data' => [
                    'mime_type' => $options['logo_mime_type'] ?? 'image/png',
                    'data' => $options['logo_base64']
                ]
            ]);
        }

        // Add reference image if provided
        if (!empty($options['reference_image_base64'])) {
            array_unshift($parts, [
                'inline_data' => [
                    'mime_type' => $options['reference_image_mime_type'] ?? 'image/png',
                    'data' => $options['reference_image_base64']
                ]
            ]);
        }

        $payload = [
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.8,
                'maxOutputTokens' => 4096,
            ]
        ];

        $response = $this->makeRequest($url, $payload);
        
        // Extract base64 image from response if available
        return $this->extractImageFromResponse($response);
    }

    protected function makeRequest(string $url, array $payload): string
    {
        $fullUrl = "{$url}?key={$this->apiKey}";

        try {
            $response = Http::timeout(60)
                ->retry(3, 1000)
                ->post($fullUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Gemini API error: {$response->status()}");
            }

            $data = $response->json();
            
            return $this->extractTextFromResponse($data);

        } catch (Exception $e) {
            Log::error('Gemini service error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw $e;
        }
    }

    protected function extractTextFromResponse(array $data): string
    {
        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return $data['candidates'][0]['content']['parts'][0]['text'];
        }

        throw new Exception('Invalid Gemini response structure');
    }

    protected function extractImageFromResponse(string $response): string
    {
        // If response contains base64 image data, extract it
        // Otherwise return the text response (which may contain description)
        if (preg_match('/data:image\/[^;]+;base64,([A-Za-z0-9+\/=]+)/', $response, $matches)) {
            return $matches[1];
        }

        // Return the text as-is (could be a description or error)
        return $response;
    }

    protected function buildStructuredPrompt(string $prompt, array $schema): string
    {
        $schemaJson = json_encode($schema, JSON_PRETTY_PRINT);
        
        return <<<PROMPT
{$prompt}

IMPORTANT: You must respond ONLY with valid JSON that matches this exact schema:
{$schemaJson}

Do not include any text before or after the JSON. Do not wrap the JSON in markdown code blocks.
Respond with ONLY the JSON object.
PROMPT;
    }

    protected function buildMockupPrompt(string $designPrompt, array $options): string
    {
        $productName = $options['product_name'] ?? 'produto';
        $productColor = $options['product_color'] ?? 'branco';
        $printArea = $options['print_area'] ?? 'frente';
        $designHint = $options['design_hint'] ?? '';

        $prompt = <<<PROMPT
Você é um designer profissional. Baseado na imagem do produto fornecida, crie um mockup realista aplicando o seguinte design:

Produto: {$productName}
Cor do produto: {$productColor}
Área de impressão: {$printArea}

Design solicitado: {$designPrompt}

PROMPT;

        if (!empty($designHint)) {
            $prompt .= "\nDica de design: {$designHint}\n";
        }

        if (!empty($options['logo_base64'])) {
            $prompt .= "\nIncorpore o logo fornecido de forma proeminente no design.\n";
        }

        if (!empty($options['reference_image_base64'])) {
            $prompt .= "\nUse a imagem de referência fornecida como inspiração para o estilo visual.\n";
        }

        $prompt .= <<<PROMPT

REGRAS IMPORTANTES:
1. NÃO altere a forma ou cor base do produto
2. Aplique o design APENAS na área de impressão especificada
3. O mockup deve parecer realista e profissional
4. Mantenha a perspectiva e iluminação originais do produto
5. O design deve ser claramente visível e bem integrado

Gere uma descrição detalhada de como o mockup ficaria, ou se possível, gere a imagem do mockup diretamente.
PROMPT;

        return $prompt;
    }

    protected function parseJsonResponse(string $response): array
    {
        // Clean up response - remove markdown code blocks if present
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);

        $data = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse JSON from Gemini', [
                'response' => $response,
                'error' => json_last_error_msg(),
            ]);
            throw new Exception('Invalid JSON response from AI: ' . json_last_error_msg());
        }

        return $data;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
EOF

# ============================================
# SUGGESTION SERVICE
# ============================================
echo "💡 Creating Suggestion Service..."

cat > app/Services/AI/SuggestionService.php << 'EOF'
<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\SuggestionServiceInterface;
use App\DTOs\AI\SuggestionRequest;
use App\DTOs\AI\SuggestionResponse;
use App\DTOs\AI\ProductSuggestion;
use App\Models\Product\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
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
            throw new Exception('Product not found');
        }

        // Get base image
        $baseImagePath = $product->base_image_path ?? $product->image_url;
        
        if (empty($baseImagePath)) {
            return $this->getFallbackMockupUrl($productId);
        }

        try {
            // Convert base image to base64
            $baseImageContent = file_get_contents($baseImagePath);
            $baseImageBase64 = base64_encode($baseImageContent);
            $baseMimeType = mime_content_type($baseImagePath) ?: 'image/png';

            $mockupResponse = $this->geminiService->generateMockupWithVision(
                $baseImageBase64,
                $baseMimeType,
                $designData['design_prompt'],
                [
                    'product_name' => $product->name,
                    'product_color' => $product->colors->first()?->name ?? 'branco',
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

            // Otherwise return fallback
            return $this->getFallbackMockupUrl($productId);

        } catch (Exception $e) {
            Log::error('Mockup generation failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
            return $this->getFallbackMockupUrl($productId);
        }
    }

    public function refineDesign(string $currentPrompt, string $feedback): string
    {
        $prompt = <<<PROMPT
Você é um assistente de design. O usuário criou um design com o seguinte prompt:

PROMPT ATUAL: {$currentPrompt}

FEEDBACK DO USUÁRIO: {$feedback}

Baseado no feedback, gere um novo prompt de design melhorado que incorpore as mudanças solicitadas.
Mantenha os elementos bons do design original e aplique as melhorias.

Responda APENAS com o novo prompt de design, sem explicações adicionais.
PROMPT;

        return $this->geminiService->generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 500,
        ]);
    }

    protected function getAvailableProducts()
    {
        return Product::with(['colors', 'printAreas'])
            ->where('is_active', true)
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
        return strlen($data) > 100 && base64_decode($data, true) !== false;
    }

    protected function saveMockupImage(string $base64Data, int $productId): string
    {
        $filename = "mockup_{$productId}_" . time() . '_' . uniqid() . '.png';
        $path = storage_path("app/public/mockups/{$filename}");
        
        // Ensure directory exists
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, base64_decode($base64Data));

        return asset("storage/mockups/{$filename}");
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
EOF

# ============================================
# FALLBACK SERVICE
# ============================================
echo "🔄 Creating Fallback Service..."

cat > app/Services/AI/FallbackSuggestionService.php << 'EOF'
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
            ->where('is_active', true)
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
EOF

# ============================================
# FORM REQUESTS
# ============================================
echo "📝 Creating Form Requests..."

cat > app/Http/Requests/AI/GetSuggestionsRequest.php << 'EOF'
<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GetSuggestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'goal' => 'required|string|min:10|max:500',
            'budget' => 'required|numeric|min:250|max:1000000',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'required_with:logo_base64|string|in:image/png,image/jpeg,image/webp',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'required_with:reference_image_base64|string|in:image/png,image/jpeg,image/webp',
            'max_suggestions' => 'sometimes|integer|min:1|max:10',
            'include_bundles' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'goal.required' => 'Descreva o seu objetivo ou necessidade',
            'goal.min' => 'Por favor, forneça mais detalhes sobre seu objetivo (mínimo 10 caracteres)',
            'budget.required' => 'Informe seu orçamento',
            'budget.numeric' => 'Orçamento deve ser um número',
            'budget.min' => 'Orçamento mínimo é 250 MT',
            'logo_mime_type.in' => 'Formato de logo inválido. Use PNG, JPEG ou WebP',
            'reference_image_mime_type.in' => 'Formato de imagem inválido. Use PNG, JPEG ou WebP',
        ];
    }
}
EOF

cat > app/Http/Requests/AI/GenerateMockupRequest.php << 'EOF'
<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class GenerateMockupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,id',
            'design_prompt' => 'required|string|min:5|max:1000',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'required_with:logo_base64|string',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'required_with:reference_image_base64|string',
            'color_id' => 'sometimes|integer|exists:product_colors,id',
            'print_area_id' => 'sometimes|integer|exists:product_print_areas,id',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Selecione um produto',
            'product_id.exists' => 'Produto não encontrado',
            'design_prompt.required' => 'Descreva o design desejado',
            'design_prompt.min' => 'Forneça mais detalhes sobre o design',
        ];
    }
}
EOF

cat > app/Http/Requests/AI/RefineDesignRequest.php << 'EOF'
<?php

namespace App\Http\Requests\AI;

use Illuminate\Foundation\Http\FormRequest;

class RefineDesignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_prompt' => 'required|string|max:1000',
            'feedback' => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'current_prompt.required' => 'Prompt atual é necessário',
            'feedback.required' => 'Forneça seu feedback para melhorar o design',
            'feedback.min' => 'Por favor, seja mais específico no seu feedback',
        ];
    }
}
EOF

# ============================================
# API RESOURCES
# ============================================
echo "📦 Creating API Resources..."

cat > app/Http/Resources/AI/SuggestionResource.php << 'EOF'
<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SuggestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => $this->resource->type,
            'title' => $this->resource->title,
            'products' => array_map(fn($p) => [
                'name' => $p->name,
                'design_prompt' => $p->designPrompt,
                'theme' => $p->theme,
                'text_to_print' => $p->textToPrint,
                'color_style' => $p->colorStyle,
                'price' => $p->price,
                'quantity' => $p->quantity,
                'total_price' => $p->getTotalPrice(),
                'formatted_price' => number_format($p->price, 2, ',', '.') . ' MT',
                'formatted_total' => number_format($p->getTotalPrice(), 2, ',', '.') . ' MT',
                'mockup_url' => $p->mockupUrl,
                'product_id' => $p->productId,
            ], $this->resource->products),
            'estimated_total' => $this->resource->estimatedTotal,
            'formatted_total' => number_format($this->resource->estimatedTotal, 2, ',', '.') . ' MT',
            'cta' => $this->resource->cta,
            'bundle_theme' => $this->resource->bundleTheme,
            'product_count' => $this->resource->getProductCount(),
            'is_bundle' => $this->resource->isBundle(),
        ];
    }
}
EOF

cat > app/Http/Resources/AI/SuggestionsCollectionResource.php << 'EOF'
<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SuggestionsCollectionResource extends ResourceCollection
{
    public $collects = SuggestionResource::class;

    public function toArray(Request $request): array
    {
        return [
            'suggestions' => $this->collection,
            'total_suggestions' => $this->collection->count(),
            'has_bundles' => $this->collection->contains(fn($s) => $s->resource->isBundle()),
        ];
    }
}
EOF

# ============================================
# CONTROLLER
# ============================================
echo "🎮 Creating Controller..."

cat > app/Http/Controllers/AI/SuggestionController.php << 'EOF'
<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\SuggestionService;
use App\DTOs\AI\SuggestionRequest;
use App\Http\Requests\AI\GetSuggestionsRequest;
use App\Http\Requests\AI\GenerateMockupRequest;
use App\Http\Requests\AI\RefineDesignRequest;
use App\Http\Resources\AI\SuggestionsCollectionResource;
use Illuminate\Http\JsonResponse;
use Exception;

class SuggestionController extends Controller
{
    public function __construct(
        private SuggestionService $suggestionService
    ) {}

    public function getSuggestions(GetSuggestionsRequest $request): JsonResponse
    {
        try {
            $suggestionRequest = SuggestionRequest::fromArray($request->validated());
            $suggestions = $this->suggestionService->getSmartSuggestions($suggestionRequest);
            
            return response()->json([
                'data' => new SuggestionsCollectionResource(collect($suggestions)),
                'goal' => $suggestionRequest->goal,
                'budget' => $suggestionRequest->budget,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Falha ao gerar sugestões. Por favor, tente novamente.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function generateMockup(GenerateMockupRequest $request): JsonResponse
    {
        try {
            $mockupUrl = $this->suggestionService->generateMockup(
                $request->product_id,
                $request->validated()
            );
            
            return response()->json([
                'data' => [
                    'mockup_url' => $mockupUrl,
                ],
                'message' => 'Mockup gerado com sucesso!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Falha ao gerar mockup',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function refineDesign(RefineDesignRequest $request): JsonResponse
    {
        try {
            $newPrompt = $this->suggestionService->refineDesign(
                $request->current_prompt,
                $request->feedback
            );
            
            return response()->json([
                'data' => [
                    'refined_prompt' => $newPrompt,
                ],
                'message' => 'Design refinado com sucesso!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Falha ao refinar design',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function healthCheck(): JsonResponse
    {
        $geminiService = app(GeminiService::class);
        
        return response()->json([
            'status' => 'operational',
            'gemini_configured' => $geminiService->isConfigured(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
EOF

# ============================================
# SERVICE PROVIDER
# ============================================
echo "🔧 Creating Service Provider..."

cat > app/Providers/AIServiceProvider.php << 'EOF'
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\GeminiService;
use App\Services\AI\SuggestionService;
use App\Services\AI\FallbackSuggestionService;
use App\Services\AI\Contracts\AIServiceInterface;
use App\Services\AI\Contracts\SuggestionServiceInterface;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiService::class);
        
        $this->app->singleton(FallbackSuggestionService::class);
        
        $this->app->singleton(SuggestionService::class, function ($app) {
            return new SuggestionService(
                $app->make(GeminiService::class),
                $app->make(FallbackSuggestionService::class)
            );
        });

        $this->app->bind(AIServiceInterface::class, GeminiService::class);
        $this->app->bind(SuggestionServiceInterface::class, SuggestionService::class);
    }

    public function boot(): void
    {
        //
    }
}
EOF

# ============================================
# ROUTES
# ============================================
echo "🛤️ Creating Routes..."

cat > routes/ai.php << 'EOF'
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AI\SuggestionController;

Route::prefix('api/v1/ai')->group(function () {
    // Get smart product suggestions
    Route::post('suggestions', [SuggestionController::class, 'getSuggestions']);
    
    // Generate mockup for a specific product
    Route::post('mockup', [SuggestionController::class, 'generateMockup']);
    
    // Refine design based on feedback
    Route::post('refine', [SuggestionController::class, 'refineDesign']);
    
    // Health check
    Route::get('health', [SuggestionController::class, 'healthCheck']);
});
EOF

# ============================================
# CONFIG
# ============================================
echo "⚙️ Creating Config..."

cat > config/ai.php << 'EOF'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    */
    
    'default' => env('AI_SERVICE', 'gemini'),
    
    /*
    |--------------------------------------------------------------------------
    | Google Gemini Configuration
    |--------------------------------------------------------------------------
    */
    
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
        'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-1.5-flash'),
        'timeout' => env('GEMINI_TIMEOUT', 60),
        'max_retries' => env('GEMINI_MAX_RETRIES', 3),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Suggestion Settings
    |--------------------------------------------------------------------------
    */
    
    'suggestions' => [
        'max_per_request' => env('AI_MAX_SUGGESTIONS', 5),
        'cache_ttl' => env('AI_CACHE_TTL', 3600), // 1 hour
        'include_bundles' => env('AI_INCLUDE_BUNDLES', true),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Mockup Generation Settings
    |--------------------------------------------------------------------------
    */
    
    'mockups' => [
        'storage_path' => env('AI_MOCKUP_PATH', 'public/mockups'),
        'max_size' => env('AI_MOCKUP_MAX_SIZE', 5242880), // 5MB
        'allowed_formats' => ['png', 'jpg', 'jpeg', 'webp'],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    */
    
    'fallback' => [
        'enabled' => env('AI_FALLBACK_ENABLED', true),
        'log_errors' => env('AI_LOG_FALLBACK_ERRORS', true),
    ],
];
EOF

# ============================================
# JOBS
# ============================================
echo "⏰ Creating Jobs..."

cat > app/Jobs/AI/GenerateBulkMockups.php << 'EOF'
<?php

namespace App\Jobs\AI;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\AI\SuggestionService;
use Illuminate\Support\Facades\Log;

class GenerateBulkMockups implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public array $products,
        public array $designData,
        public ?string $callbackUrl = null
    ) {}

    public function handle(SuggestionService $suggestionService): void
    {
        $results = [];

        foreach ($this->products as $productId) {
            try {
                $mockupUrl = $suggestionService->generateMockup($productId, $this->designData);
                $results[$productId] = [
                    'success' => true,
                    'mockup_url' => $mockupUrl,
                ];
            } catch (\Exception $e) {
                Log::error('Bulk mockup generation failed', [
                    'product_id' => $productId,
                    'error' => $e->getMessage(),
                ]);
                $results[$productId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        // TODO: Send callback with results if URL provided
        if ($this->callbackUrl) {
            Log::info('Bulk mockup generation completed', [
                'results' => $results,
                'callback_url' => $this->callbackUrl,
            ]);
        }
    }
}
EOF

# ============================================
# EVENTS
# ============================================
echo "📡 Creating Events..."

cat > app/Events/AI/SuggestionsGenerated.php << 'EOF'
<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuggestionsGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $goal,
        public float $budget,
        public int $suggestionCount,
        public array $metadata = []
    ) {}
}
EOF

cat > app/Events/AI/MockupGenerated.php << 'EOF'
<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MockupGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $productId,
        public string $mockupUrl,
        public array $designData = []
    ) {}
}
EOF

# ============================================
# TESTS
# ============================================
echo "🧪 Creating Tests..."

mkdir -p tests/Feature/AI
mkdir -p tests/Unit/Services/AI

cat > tests/Feature/AI/SuggestionTest.php << 'EOF'
<?php

namespace Tests\Feature\AI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product\Product;
use App\Services\AI\GeminiService;
use Mockery;

class SuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_smart_suggestions(): void
    {
        // Create test products
        Product::factory()->count(5)->create();

        $requestData = [
            'goal' => 'brindes para evento de empresa com 50 pessoas',
            'budget' => 10000,
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'suggestions' => [
                             '*' => [
                                 'type',
                                 'title',
                                 'products',
                                 'estimated_total',
                                 'cta',
                             ]
                         ],
                         'total_suggestions',
                         'has_bundles',
                     ],
                     'goal',
                     'budget',
                 ]);
    }

    public function test_validates_minimum_budget(): void
    {
        $requestData = [
            'goal' => 'brindes para evento',
            'budget' => 100, // Below minimum
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['budget']);
    }

    public function test_validates_goal_length(): void
    {
        $requestData = [
            'goal' => 'short', // Too short
            'budget' => 5000,
        ];

        $response = $this->postJson('/api/v1/ai/suggestions', $requestData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['goal']);
    }

    public function test_health_check_returns_status(): void
    {
        $response = $this->getJson('/api/v1/ai/health');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'gemini_configured',
                     'timestamp',
                 ]);
    }

    public function test_can_refine_design(): void
    {
        $this->mock(GeminiService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('generateText')->andReturn('Novo prompt refinado com logo moderno');
        });

        $requestData = [
            'current_prompt' => 'Design simples com logo',
            'feedback' => 'Quero cores mais vibrantes e logo maior',
        ];

        $response = $this->postJson('/api/v1/ai/refine', $requestData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         'refined_prompt',
                     ],
                     'message',
                 ]);
    }
}
EOF

cat > tests/Unit/Services/AI/SuggestionServiceTest.php << 'EOF'
<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AI\SuggestionService;
use App\Services\AI\GeminiService;
use App\Services\AI\FallbackSuggestionService;
use App\DTOs\AI\SuggestionRequest;
use App\Models\Product\Product;
use Mockery;

class SuggestionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_uses_fallback_when_gemini_not_configured(): void
    {
        Product::factory()->count(3)->create();

        $geminiMock = Mockery::mock(GeminiService::class);
        $geminiMock->shouldReceive('isConfigured')->andReturn(false);

        $fallbackService = new FallbackSuggestionService();
        $service = new SuggestionService($geminiMock, $fallbackService);

        $request = new SuggestionRequest(
            goal: 'brindes para conferência',
            budget: 5000
        );

        $suggestions = $service->getSmartSuggestions($request);

        $this->assertIsArray($suggestions);
    }

    public function test_respects_budget_constraints(): void
    {
        Product::factory()->create(['price' => 1000, 'min_quantity' => 1]);
        Product::factory()->create(['price' => 2000, 'min_quantity' => 1]);
        Product::factory()->create(['price' => 10000, 'min_quantity' => 1]);

        $fallbackService = new FallbackSuggestionService();

        $request = new SuggestionRequest(
            goal: 'brindes para evento',
            budget: 3000
        );

        $suggestions = $fallbackService->getSuggestions($request);

        foreach ($suggestions as $suggestion) {
            $this->assertLessThanOrEqual(3000, $suggestion->estimatedTotal);
        }
    }

    public function test_includes_bundles_when_requested(): void
    {
        Product::factory()->count(5)->create(['price' => 500, 'min_quantity' => 1]);

        $fallbackService = new FallbackSuggestionService();

        $request = new SuggestionRequest(
            goal: 'brindes para evento',
            budget: 10000,
            includeBundles: true
        );

        $suggestions = $fallbackService->getSuggestions($request);

        $hasBundle = collect($suggestions)->contains(fn($s) => $s->type === 'bundle');
        $this->assertTrue($hasBundle);
    }
}
EOF

cat > tests/Unit/DTOs/SuggestionRequestTest.php << 'EOF'
<?php

namespace Tests\Unit\DTOs;

use Tests\TestCase;
use App\DTOs\AI\SuggestionRequest;

class SuggestionRequestTest extends TestCase
{
    public function test_creates_from_array(): void
    {
        $data = [
            'goal' => 'brindes para evento',
            'budget' => 5000,
            'logo_base64' => 'base64data',
            'logo_mime_type' => 'image/png',
        ];

        $request = SuggestionRequest::fromArray($data);

        $this->assertEquals('brindes para evento', $request->goal);
        $this->assertEquals(5000, $request->budget);
        $this->assertTrue($request->hasLogo());
        $this->assertFalse($request->hasReferenceImage());
    }

    public function test_default_values(): void
    {
        $request = new SuggestionRequest(
            goal: 'test',
            budget: 1000
        );

        $this->assertEquals(5, $request->maxSuggestions);
        $this->assertTrue($request->includeBundles);
    }
}
EOF

# ============================================
# ENV EXAMPLE
# ============================================
echo "📄 Creating .env example..."

cat > .env.ai.example << 'EOF'
# AI Service Configuration
AI_SERVICE=gemini

# Google Gemini API
GEMINI_API_KEY=your-gemini-api-key-here
GEMINI_BASE_URL=https://generativelanguage.googleapis.com/v1beta
GEMINI_MODEL=gemini-1.5-flash
GEMINI_VISION_MODEL=gemini-1.5-flash
GEMINI_TIMEOUT=60
GEMINI_MAX_RETRIES=3

# AI Suggestion Settings
AI_MAX_SUGGESTIONS=5
AI_CACHE_TTL=3600
AI_INCLUDE_BUNDLES=true

# Mockup Settings
AI_MOCKUP_PATH=public/mockups
AI_MOCKUP_MAX_SIZE=5242880

# Fallback Settings
AI_FALLBACK_ENABLED=true
AI_LOG_FALLBACK_ERRORS=true
EOF

# ============================================
# STORAGE SETUP
# ============================================
echo "📂 Creating storage directories..."

mkdir -p storage/app/public/mockups

# ============================================
# FINAL OUTPUT
# ============================================
echo ""
echo "✅ Amazing Brindes AI Suggestions Module setup completed successfully!"
echo ""
echo "📊 Created:"
echo "   - 3 DTOs (SuggestionRequest, ProductSuggestion, SuggestionResponse)"
echo "   - 2 Contracts/Interfaces"
echo "   - 3 AI Services (Gemini, Suggestion, Fallback)"
echo "   - 3 Form Requests"
echo "   - 2 API Resources"
echo "   - 1 Controller"
echo "   - 1 Service Provider"
echo "   - 1 Routes file"
echo "   - 1 Job (GenerateBulkMockups)"
echo "   - 2 Events"
echo "   - 1 Config file"
echo "   - 3 Test files"
echo "   - 1 .env example"
echo "   - Storage directory for mockups"
echo ""
echo "🚀 Next steps:"
echo ""
echo "   1. Add Gemini API key to .env:"
echo "      GEMINI_API_KEY=your-api-key-here"
echo ""
echo "   2. Register the Service Provider in config/app.php:"
echo "      App\\Providers\\AIServiceProvider::class,"
echo ""
echo "   3. Register the routes in routes/api.php:"
echo "      require __DIR__.'/ai.php';"
echo ""
echo "   4. Create storage symlink:"
echo "      php artisan storage:link"
echo ""
echo "   5. Publish config if needed:"
echo "      php artisan vendor:publish --tag=ai-config"
echo ""
echo "   6. Run tests:"
echo "      php artisan test --filter=AI"
echo ""
echo "📋 API Endpoints Created:"
echo "   POST /api/v1/ai/suggestions  - Get smart product suggestions"
echo "   POST /api/v1/ai/mockup       - Generate mockup for product"
echo "   POST /api/v1/ai/refine       - Refine design based on feedback"
echo "   GET  /api/v1/ai/health       - AI service health check"
echo ""
echo "💡 Features:"
echo "   - Gemini AI integration for smart recommendations"
echo "   - Goal and budget-based product suggestions"
echo "   - Single product and bundle recommendations"
echo "   - AI mockup generation with vision model"
echo "   - Logo and reference image support"
echo "   - Design refinement with feedback"
echo "   - Automatic fallback to static suggestions"
echo "   - Response caching for performance"
echo "   - Structured JSON responses from AI"
echo "   - Portuguese (Mozambique) language support"
echo ""
echo "⚠️ Important:"
echo "   - Get your Gemini API key from: https://makersuite.google.com/app/apikey"
echo "   - Free tier has rate limits - implement rate limiting for production"
echo "   - Mockup storage requires storage:link to be accessible"
echo ""
echo "🎉 AI Suggestions Module ready for Amazing Brindes!"
