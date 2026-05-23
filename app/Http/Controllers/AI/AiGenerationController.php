<?php

namespace App\Http\Controllers\AI;

use App\Enums\AiGenerationStatus;
use App\Exceptions\InsufficientCreditsException;
use App\Http\Controllers\Controller;
use App\Models\AI\AiGeneration;
use App\Services\AI\AiGenerationService;
use App\Services\AI\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiGenerationController extends Controller
{
    public function __construct(private AiGenerationService $generationService) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = AiGeneration::where('user_id', $user->id)->latest();

        if ($request->filled('project_request_id')) {
            $query->where('project_request_id', (int) $request->get('project_request_id'));
        }

        if ($request->boolean('exclude_superseded', true)) {
            $query->where('status', '!=', AiGenerationStatus::Superseded);
        }

        $items = $query->paginate((int) $request->get('per_page', 20));

        return response()->json($items);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:facade_render,floorplan,other',
            'design_prompt' => 'nullable|string|max:2000',
            'project_request_id' => 'nullable|exists:project_requests,id',
            'reference_image_base64' => 'nullable|string',
            'reference_image_mime_type' => 'nullable|string',
            'logo_base64' => 'nullable|string',
            'logo_mime_type' => 'nullable|string',
            'house_image_url' => 'nullable|string|max:2048',
            'parent_generation_id' => 'nullable|exists:ai_generations,id',
        ]);

        try {
            $generation = $this->generationService->create($request->user(), $validated, $request);

            return response()->json(['data' => $generation], 201);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['message' => $e->getMessage()], 402);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Falha ao gerar imagem.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $generation = AiGeneration::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(['data' => $generation]);
    }

    public function refine(Request $request, int $id): JsonResponse
    {
        $parent = AiGeneration::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'design_prompt' => 'required|string|max:2000',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $prompt = trim($validated['design_prompt']);
        if (!empty($validated['feedback'])) {
            $prompt .= ' Feedback: ' . $validated['feedback'];
        }

        $data = array_merge($validated, [
            'type' => $parent->type->value,
            'design_prompt' => $prompt,
            'parent_generation_id' => $parent->id,
            'project_request_id' => $parent->project_request_id,
            'house_image_url' => $parent->image_url,
        ]);

        try {
            $generation = $this->generationService->create($request->user(), $data, $request);

            return response()->json(['data' => $generation], 201);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['message' => $e->getMessage()], 402);
        }
    }

    public function healthCheck(): JsonResponse
    {
        $gemini = app(GeminiService::class);

        return response()->json([
            'status' => 'operational',
            'gemini_configured' => $gemini->isConfigured(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
