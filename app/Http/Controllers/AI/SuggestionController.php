<?php

namespace App\Http\Controllers\AI;

use App\Http\Controllers\Controller;
use App\Services\AI\SuggestionService;
use App\Services\AI\GeminiService;
use App\DTOs\AI\SuggestionRequest;
use App\Http\Requests\AI\GetSuggestionsRequest;
use App\Http\Requests\AI\GenerateMockupRequest;
use App\Http\Requests\AI\RefineDesignRequest;
use App\Http\Resources\AI\SuggestionsCollectionResource;
use Illuminate\Http\JsonResponse;
use Exception;

/**
 * @group AI Suggestions
 * 
 * AI-powered product suggestions and mockup generation endpoints
 */
class SuggestionController extends Controller
{
    public function __construct(
        private SuggestionService $suggestionService
    ) {}

    /**
     * Get Smart Product Suggestions
     * 
     * Generate AI-powered product suggestions based on goal and budget.
     * Returns product recommendations with mockups.
     * 
     * @param GetSuggestionsRequest $request
     * @return JsonResponse
     */
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

    /**
     * Generate Mockup
     * 
     * Generate a visual mockup for a specific product with custom design.
     * 
     * @param GenerateMockupRequest $request
     * @return JsonResponse
     */
    public function generateMockup(GenerateMockupRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            \Log::info('GenerateMockup', [
                'product_id' => $validated['product_id'] ?? null,
                'has_logo' => !empty($validated['logo_base64']),
                'has_reference' => !empty($validated['reference_image_base64']),
            ]);
            
            $mockupPath = $this->suggestionService->generateMockup(
                $request->product_id,
                $validated
            );
            
            // Convert relative path to full URL (same pattern as product images)
            $baseUrl = $request->getSchemeAndHttpHost();
            $mockupUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($mockupPath, '/');
            
            \Log::info('GenerateMockup success', [
                'product_id' => $request->product_id,
                'mockup_url' => $mockupUrl,
            ]);
            
            return response()->json([
                'data' => [
                    'mockup_url' => $mockupUrl,
                ],
                'message' => 'Mockup gerado com sucesso!'
            ]);
        } catch (Exception $e) {
            \Log::error('GenerateMockup error', [
                'product_id' => $request->product_id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Falha ao gerar mockup',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function generateHouse(GenerateMockupRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $generationId = (string) \Illuminate\Support\Str::uuid();

            $housePath = $this->suggestionService->generateHouseRender(
                $request->product_id,
                $validated
            );

            $baseUrl = $request->getSchemeAndHttpHost();
            $houseImageUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($housePath, '/');

            return response()->json([
                'data' => [
                    'generation_id' => $generationId,
                    'house_image_url' => $houseImageUrl,
                ],
                'message' => 'Render da casa gerado com sucesso!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Falha ao gerar imagem da casa',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function generateFloorPlan(GenerateMockupRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $generationId = $request->input('generation_id') ?: (string) \Illuminate\Support\Str::uuid();

            $houseImageUrl = (string) $request->input('house_image_url', '');
            $floorPlanPath = $this->suggestionService->generateFloorPlan(
                $request->product_id,
                $validated,
                $houseImageUrl
            );

            $baseUrl = $request->getSchemeAndHttpHost();
            $floorPlanImageUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($floorPlanPath, '/');

            return response()->json([
                'data' => [
                    'generation_id' => $generationId,
                    'floorplan_image_url' => $floorPlanImageUrl,
                ],
                'message' => 'Planta gerada com sucesso!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Falha ao gerar planta',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Refine Design
     * 
     * Refine an existing design prompt based on user feedback.
     * 
     * @param RefineDesignRequest $request
     * @return JsonResponse
     */
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

    /**
     * AI Service Health Check
     * 
     * Check if the AI service (Gemini) is properly configured and operational.
     * 
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $geminiService = app(GeminiService::class);
            
            return response()->json([
                'status' => 'operational',
                'gemini_configured' => $geminiService->isConfigured(),
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'gemini_configured' => false,
                'error' => config('app.debug') ? $e->getMessage() : null,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }
    }
}
