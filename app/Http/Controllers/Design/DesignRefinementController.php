<?php

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Controller;
use App\Services\Design\DesignRefinementService;
use App\Http\Requests\Design\CreateRefinementRequest;
use App\Http\Resources\Design\DesignRefinementResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DesignRefinementController extends Controller
{
    public function __construct(
        protected DesignRefinementService $refinementService
    ) {}

    public function index(int $designId): JsonResponse
    {
        $refinements = $this->refinementService->getDesignRefinements($designId);

        return response()->json([
            'data' => DesignRefinementResource::collection($refinements)
        ]);
    }

    public function store(CreateRefinementRequest $request, int $designId): JsonResponse
    {
        $refinement = $this->refinementService->createRefinement(
            $designId,
            $request->validated()
        );

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Refinamento criado com sucesso'
        ], 201);
    }

    public function latest(int $designId): JsonResponse
    {
        $refinement = $this->refinementService->getLatestRefinement($designId);

        if (!$refinement) {
            return response()->json([
                'message' => 'Nenhum refinamento encontrado'
            ], 404);
        }

        return response()->json([
            'data' => new DesignRefinementResource($refinement)
        ]);
    }

    public function saveMockup(Request $request, int $designId, int $refinementId): JsonResponse
    {
        $request->validate([
            'mockup_url' => 'nullable|url|max:1000',
            'mockup_base64' => 'nullable|string',
        ]);

        $refinement = $this->refinementService->saveRefinementMockup(
            $refinementId,
            $request->get('mockup_url'),
            $request->get('mockup_base64')
        );

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Mockup do refinamento salvo com sucesso'
        ]);
    }

    public function markAsFailed(Request $request, int $designId, int $refinementId): JsonResponse
    {
        $metadata = $request->get('metadata', []);
        $refinement = $this->refinementService->markAsFailed($refinementId, $metadata);

        return response()->json([
            'data' => new DesignRefinementResource($refinement),
            'message' => 'Refinamento marcado como falha'
        ]);
    }
}
