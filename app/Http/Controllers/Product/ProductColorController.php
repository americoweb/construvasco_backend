<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductColorService;
use App\Http\Requests\Product\CreateProductColorRequest;
use App\Http\Resources\Product\ProductColorResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductColorController extends Controller
{
    public function __construct(
        protected ProductColorService $colorService
    ) {}

    public function index(int $productId): JsonResponse
    {
        $colors = $this->colorService->getProductColors($productId);

        return response()->json([
            'data' => ProductColorResource::collection($colors)
        ]);
    }

    public function store(CreateProductColorRequest $request, int $productId): JsonResponse
    {
        $color = $this->colorService->create($productId, $request->validated());

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Cor adicionada com sucesso'
        ], 201);
    }

    public function update(Request $request, int $productId, int $colorId): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'hex_code' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
        ]);

        $color = $this->colorService->update($colorId, $validated);

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Cor atualizada com sucesso'
        ]);
    }

    public function destroy(int $productId, int $colorId): JsonResponse
    {
        $this->colorService->delete($colorId);

        return response()->json([
            'message' => 'Cor removida com sucesso'
        ]);
    }

    public function updateStock(Request $request, int $productId, int $colorId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $color = $this->colorService->updateStock($colorId, $request->get('quantity'));

        return response()->json([
            'data' => new ProductColorResource($color),
            'message' => 'Estoque atualizado com sucesso'
        ]);
    }
}
