<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiService implements AIServiceInterface
{
    protected ?string $apiKey;
    protected string $baseUrl;
    protected string $model;
    protected string $visionModel;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key');
        $this->baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
        $this->model = config('ai.gemini.model', 'gemini-2.5-pro');
        $this->visionModel = config('ai.gemini.vision_model', 'gemini-2.5-flash-image'); // Use gemini-2.5-flash-image for image generation (matches MVP)
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
        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";
        
        $systemInstruction = $options['system_instruction'] ?? null;
        
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => $options['temperature'] ?? 0.3,
                'maxOutputTokens' => $options['max_tokens'] ?? 2048,
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
        ];
        
        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $response = $this->makeRequestForJson($url, $payload);
        return $this->parseJsonResponse($response);
    }

    public function generateMockupWithVision(
        string $baseImageBase64,
        string $baseMimeType,
        string $designPrompt,
        array $options = []
    ): string {
        $url = "{$this->baseUrl}/models/{$this->visionModel}:generateContent";
        
        Log::info('Gemini mockup generation', [
            'model' => $this->visionModel,
            'has_logo' => !empty($options['logo_base64']),
            'has_reference' => !empty($options['reference_image_base64']),
        ]);

        // Build parts array - base image FIRST
        $parts = [
            [
                'inlineData' => [
                    'mimeType' => $baseMimeType,
                    'data' => $baseImageBase64
                ]
            ]
        ];

        // Add logo if provided
        if (!empty($options['logo_base64'])) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $options['logo_mime_type'] ?? 'image/png',
                    'data' => $options['logo_base64']
                ]
            ];
        }

        // Add reference image if provided
        if (!empty($options['reference_image_base64'])) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $options['reference_image_mime_type'] ?? 'image/png',
                    'data' => $options['reference_image_base64']
                ]
            ];
        }

        // Build and add text prompt last
        $textPrompt = $this->buildMockupPrompt($designPrompt, $options);
        $parts[] = ['text' => $textPrompt];

        $payload = [
            'contents' => [['parts' => $parts]],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 4096,
                'responseModalities' => ['IMAGE', 'TEXT'],
            ]
        ];
        
        $data = $this->makeRequestForData($url, $payload);
        
        // Extract base64 image from response
        try {
            $result = $this->extractImageFromResponseData($data);
            Log::info('Gemini image extracted', ['size' => strlen($result)]);
            return $result;
        } catch (Exception $e) {
            Log::error('Gemini image extraction failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function makeRequest(string $url, array $payload): string
    {
        $fullUrl = "{$url}?key={$this->apiKey}";

        try {
            $httpClient = Http::timeout(60)
                ->retry(3, 1000);

            // Configure SSL certificate for local development (WAMP/Windows)
            // Only use local cert path if APP_ENV is 'local', not in production
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically

            $response = $httpClient->post($fullUrl, $payload);

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

    protected function makeRequestForData(string $url, array $payload): array
    {
        $fullUrl = "{$url}?key={$this->apiKey}";

        try {
            $httpClient = Http::timeout(60)->retry(3, 1000);

            // Configure SSL certificate for local development (WAMP/Windows)
            // Only use local cert path if APP_ENV is 'local', not in production
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically

            $response = $httpClient->post($fullUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Gemini API error: {$response->status()}");
            }

            return $response->json();

        } catch (Exception $e) {
            Log::error('Gemini service error', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);
            throw $e;
        }
    }

    protected function makeRequestForJson(string $url, array $payload): string
    {
        $fullUrl = "{$url}?key={$this->apiKey}";

        try {
            $httpClient = Http::timeout(60)
                ->retry(3, 1000);

            // Configure SSL certificate for local development (WAMP/Windows)
            // Only use local cert path if APP_ENV is 'local', not in production
            $appEnv = env('APP_ENV', app()->environment());
            if ($appEnv === 'local' && app()->environment('local')) {
                $certPath = 'C:\wamp64\bin\php\php8.2.18\extras\ssl\cacert.pem';
                if (file_exists($certPath)) {
                    $httpClient->withOptions([
                        'verify' => $certPath,
                    ]);
                }
            }
            // In production, Laravel's Http client will use system's default CA bundle automatically

            $response = $httpClient->post($fullUrl, $payload);

            if (!$response->successful()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new Exception("Gemini API error: {$response->status()}");
            }

            $data = $response->json();
            
            // For JSON responses with responseMimeType, extract text directly
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new Exception('Invalid JSON response structure');

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

    protected function extractImageFromResponseData(array $data): string
    {
        // Check for inlineData in response
        if (isset($data['candidates'][0]['content']['parts'])) {
            foreach ($data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['inlineData']['data'])) {
                    $mimeType = $part['inlineData']['mimeType'] ?? 'image/png';
                    return "data:{$mimeType};base64,{$part['inlineData']['data']}";
                }
            }
        }

        // Fallback: try to extract from text response
        if (isset($data['candidates'][0]['content']['parts'])) {
            foreach ($data['candidates'][0]['content']['parts'] as $part) {
                if (isset($part['text']) && preg_match('/data:image\/[^;]+;base64,([A-Za-z0-9+\/=]+)/', $part['text'], $matches)) {
                    return $matches[0];
                }
            }
        }

        Log::error('No image data found in Gemini response');
        throw new Exception('No image data found in Gemini response.');
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
        $buildingType = $options['building_type'] ?? 'moradia unifamiliar';
        $materials = $options['materials'] ?? 'alvenaria, betão e acabamentos tropicais';
        $hasReference = !empty($options['reference_image_base64']);

        $imageDescriptions = 'A primeira imagem é uma referência neutra de partida para o render.';
        if ($hasReference) {
            $imageDescriptions .= "\nA segunda imagem é uma referência visual do cliente (fotografia do terreno, fachada desejada ou moodboard).";
        }

        $contextHint = $options['context_hint'] ?? '';

        return <<<PROMPT
Você é um assistente de visualização arquitectónica para projectos em Moçambique.
Sua tarefa é gerar um render realista de fachada/planta conceptual com base no briefing do cliente.

{$imageDescriptions}

Briefing do cliente: "{$designPrompt}".

Tipo de edifício: {$buildingType}.
Materiais e acabamentos sugeridos: {$materials}.
Contexto: clima tropical, luz diurna natural, escala humana, vegetação local quando aplicável.
{$contextHint}

**Sua tarefa:**
1. Interprete o programa, estilo e materiais descritos no briefing.
2. Produza uma imagem arquitectónica coerente com o contexto moçambicano.
3. Se houver imagem de referência, use-a como guia de volumetria, proporções e estilo — sem copiar elementos protegidos.
4. Priorize legibilidade da fachada, entrada principal e relação com o terreno.

**INSTRUÇÃO CRÍTICA:** Não inclua texto, marcas de água ou logótipos comerciais. A saída deve ser apenas a imagem do render arquitectónico.
PROMPT;
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
