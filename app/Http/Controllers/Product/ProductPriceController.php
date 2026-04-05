<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Services\Product\ProductPricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductPriceController extends Controller
{
    public function __construct(
        protected ProductPricingService $pricingService
    ) {}

    public function calculatePrice(Request $request, int $productId): JsonResponse
    {
        $product = Product::findOrFail($productId);

        $validated = $request->validate([
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'size_id' => 'nullable|integer|exists:product_sizes,id',
        ]);

        $size = null;
        if (isset($validated['size_id'])) {
            $size = $product->sizes()->find($validated['size_id']);
        }

        $details = $this->pricingService->calculatePriceWithDetails(
            $product,
            $size,
            $validated['width_cm'] ?? null,
            $validated['height_cm'] ?? null
        );

        return response()->json([
            'data' => $details
        ]);
    }
}

