<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductPrintAreaService;
use App\Http\Requests\Product\CreateProductPrintAreaRequest;
use App\Http\Resources\Product\ProductPrintAreaResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductPrintAreaController extends Controller
{
    public function __construct(
        protected ProductPrintAreaService $printAreaService
    ) {}

    public function index(int $productId): JsonResponse
    {
        $areas = $this->printAreaService->getProductPrintAreas($productId);

        return response()->json([
            'data' => ProductPrintAreaResource::collection($areas)
        ]);
    }

    public function store(CreateProductPrintAreaRequest $request, int $productId): JsonResponse
    {
        $area = $this->printAreaService->create($productId, $request->validated());

        return response()->json([
            'data' => new ProductPrintAreaResource($area),
            'message' => 'Área de impressão adicionada com sucesso'
        ], 201);
    }

    public function update(Request $request, int $productId, int $areaId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'max_width_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'additional_price' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $area = $this->printAreaService->update($areaId, $validated);

        return response()->json([
            'data' => new ProductPrintAreaResource($area),
            'message' => 'Área de impressão atualizada com sucesso'
        ]);
    }

    public function destroy(int $productId, int $areaId): JsonResponse
    {
        $this->printAreaService->delete($areaId);

        return response()->json([
            'message' => 'Área de impressão removida com sucesso'
        ]);
    }
}
