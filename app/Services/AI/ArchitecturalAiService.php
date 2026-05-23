<?php

namespace App\Services\AI;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ArchitecturalAiService
{
    /** Minimal blank PNG for vision-model text-to-image prompts. */
    private const BLANK_PNG_BASE64 = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';

    public function __construct(private GeminiService $geminiService) {}

    public function generateHouseRender(array $designData): string
    {
        $basePrompt = trim((string) ($designData['design_prompt'] ?? ''));
        $prompt = "Crie um render arquitetónico realista de uma casa em Moçambique, com foco em fachada e volumetria. {$basePrompt}";

        return $this->generateRender($prompt, $designData, 'house');
    }

    public function generateFloorPlan(array $designData, string $houseImageUrl = ''): string
    {
        $basePrompt = trim((string) ($designData['design_prompt'] ?? ''));
        $prompt = "Crie uma planta baixa técnica correspondente à casa descrita, com divisão de ambientes, circulação e proporções realistas. {$basePrompt}";

        if (!empty($houseImageUrl) && empty($designData['reference_image_base64'])) {
            $content = @file_get_contents($houseImageUrl);
            if ($content !== false) {
                $designData['reference_image_base64'] = base64_encode($content);
                $designData['reference_image_mime_type'] = 'image/png';
            }
        }

        return $this->generateRender($prompt, $designData, 'floorplan');
    }

    public function refineDesign(string $currentPrompt, string $feedback): string
    {
        $prompt = <<<REFINE_PROMPT
Você é um assistente de design arquitectónico. O utilizador criou um briefing com o seguinte prompt:

PROMPT ACTUAL: {$currentPrompt}

FEEDBACK DO UTILIZADOR: {$feedback}

Gere um novo prompt melhorado que incorpore as mudanças solicitadas.
Responda APENAS com o novo prompt, sem explicações adicionais.
REFINE_PROMPT;

        return $this->geminiService->generateText($prompt, [
            'temperature' => 0.6,
            'max_tokens' => 500,
        ]);
    }

    private function generateRender(string $prompt, array $designData, string $type): string
    {
        $response = $this->geminiService->generateMockupWithVision(
            self::BLANK_PNG_BASE64,
            'image/png',
            $prompt,
            [
                'building_type' => $designData['building_type'] ?? 'moradia unifamiliar',
                'materials' => $designData['materials'] ?? 'alvenaria e betão com acabamentos tropicais',
                'context_hint' => $designData['context_hint'] ?? '',
                'reference_image_base64' => $designData['reference_image_base64'] ?? null,
                'reference_image_mime_type' => $designData['reference_image_mime_type'] ?? null,
            ]
        );

        return $this->saveImage($response, $type);
    }

    private function saveImage(string $base64Data, string $type): string
    {
        $filename = "render_{$type}_" . time() . '_' . uniqid() . '.png';
        $relativePath = "renders/{$filename}";

        $imageData = $base64Data;
        if (str_starts_with($base64Data, 'data:image')) {
            $imageData = preg_replace('/^data:image\/[^;]+;base64,/', '', $base64Data);
        }

        $decoded = base64_decode($imageData, true);
        if ($decoded === false) {
            Log::error('Failed to decode architectural render', ['type' => $type]);
            throw new Exception('Failed to decode image data');
        }

        Storage::disk('public')->put($relativePath, $decoded);

        return $relativePath;
    }
}
