<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductSize;
use App\Models\Product\ProductSizeRestriction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductSizeController extends Controller
{
    public function index(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $sizes = $product->activeSizes()->get();

        return response()->json([
            'data' => $sizes->map(function ($size) {
                return [
                    'id' => $size->id,
                    'name' => $size->name,
                    'width_cm' => $size->width_cm,
                    'height_cm' => $size->height_cm,
                    'is_predefined' => $size->is_predefined,
                    'is_custom' => $size->is_custom,
                    'fixed_price' => $size->fixed_price,
                    'dimensions' => $size->dimensions,
                    'area_sqm' => $size->area_sqm,
                ];
            })
        ]);
    }

    public function getRestrictions(int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);
        $restriction = $product->sizeRestrictions()->first();

        if (!$restriction) {
            return response()->json([
                'data' => null
            ]);
        }

        return response()->json([
            'data' => [
                'id' => $restriction->id,
                'min_width_cm' => $restriction->min_width_cm,
                'max_width_cm' => $restriction->max_width_cm,
                'min_height_cm' => $restriction->min_height_cm,
                'max_height_cm' => $restriction->max_height_cm,
                'min_aspect_ratio' => $restriction->min_aspect_ratio,
                'max_aspect_ratio' => $restriction->max_aspect_ratio,
                'step_increment_cm' => $restriction->step_increment_cm,
            ]
        ]);
    }

    public function storeRestrictions(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'min_width_cm' => 'nullable|numeric|min:0',
            'max_width_cm' => 'nullable|numeric|min:0',
            'min_height_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'min_aspect_ratio' => 'nullable|numeric|min:0',
            'max_aspect_ratio' => 'nullable|numeric|min:0',
            'step_increment_cm' => 'nullable|numeric|min:0',
        ]);

        // Check if restrictions already exist
        $existing = $product->sizeRestrictions()->first();
        if ($existing) {
            return response()->json([
                'message' => 'Restrições já existem. Use o endpoint de atualização.',
                'data' => $existing
            ], 400);
        }

        $restriction = ProductSizeRestriction::create([
            'product_id' => $productId,
            ...$validated
        ]);

        return response()->json([
            'data' => $restriction,
            'message' => 'Restrições criadas com sucesso'
        ], 201);
    }

    public function updateRestrictions(Request $request, int $productId, int $restrictionId): JsonResponse
    {
        $restriction = ProductSizeRestriction::where('product_id', $productId)
            ->findOrFail($restrictionId);

        $validated = $request->validate([
            'min_width_cm' => 'nullable|numeric|min:0',
            'max_width_cm' => 'nullable|numeric|min:0',
            'min_height_cm' => 'nullable|numeric|min:0',
            'max_height_cm' => 'nullable|numeric|min:0',
            'min_aspect_ratio' => 'nullable|numeric|min:0',
            'max_aspect_ratio' => 'nullable|numeric|min:0',
            'step_increment_cm' => 'nullable|numeric|min:0',
        ]);

        $restriction->update($validated);

        return response()->json([
            'data' => $restriction,
            'message' => 'Restrições atualizadas com sucesso'
        ]);
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'is_predefined' => 'boolean',
            'is_custom' => 'boolean',
            'fixed_price' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $size = ProductSize::create([
            'product_id' => $productId,
            ...$validated
        ]);

        return response()->json([
            'data' => $size,
            'message' => 'Tamanho adicionado com sucesso'
        ], 201);
    }

    public function update(Request $request, int $productId, int $sizeId): JsonResponse
    {
        $size = ProductSize::where('product_id', $productId)
            ->findOrFail($sizeId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:100',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'is_predefined' => 'sometimes|boolean',
            'is_custom' => 'sometimes|boolean',
            'fixed_price' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $size->update($validated);

        return response()->json([
            'data' => $size,
            'message' => 'Tamanho atualizado com sucesso'
        ]);
    }

    public function destroy(int $productId, int $sizeId): JsonResponse
    {
        $size = ProductSize::where('product_id', $productId)
            ->findOrFail($sizeId);

        $size->delete();

        return response()->json([
            'message' => 'Tamanho removido com sucesso'
        ]);
    }
}

