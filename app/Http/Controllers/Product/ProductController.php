<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Http\Requests\Product\CreateProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Requests\Product\UploadImageRequest;
use App\Http\Resources\Product\ProductResource;
use App\Http\Resources\Product\ProductListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => ProductListResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Produto criado com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductWithDetails($id);
        $product->load(['categories.parent', 'tags', 'activeSizes', 'sizeRestrictions']);

        return response()->json([
            'data' => new ProductResource($product)
        ]);
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);
        $product->load(['activeColors', 'activePrintAreas', 'categories.parent', 'tags', 'activeSizes', 'sizeRestrictions']);

        return response()->json([
            'data' => new ProductResource($product)
        ]);
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->update($id, $request->validated());

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Produto atualizado com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->delete($id);

        return response()->json([
            'message' => 'Produto excluído com sucesso'
        ]);
    }

    public function active(Request $request): JsonResponse
    {
        $filters = [];
        
        // Filter by category slug
        if ($request->has('category')) {
            $filters['category'] = $request->get('category');
        }
        
        // Filter by subcategory slug
        if ($request->has('subcategory')) {
            $filters['subcategory'] = $request->get('subcategory');
        }
        
        // Filter by search query
        if ($request->has('search')) {
            $filters['search'] = $request->get('search');
        }
        
        $products = $this->productService->getActiveProducts($filters);

        return response()->json([
            'data' => ProductListResource::collection($products)
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = $this->productService->getFeaturedProducts();

        return response()->json([
            'data' => ProductListResource::collection($products)
        ]);
    }

    public function toggleFeatured(int $id): JsonResponse
    {
        $product = $this->productService->toggleFeatured($id);

        return response()->json([
            'data' => new ProductResource($product),
            'message' => $product->is_featured ? 'Produto destacado' : 'Produto removido dos destaques'
        ]);
    }

    public function calculatePrice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $calculation = $this->productService->calculateOrderTotal(
            $id,
            $request->get('quantity')
        );

        return response()->json([
            'data' => $calculation
        ]);
    }

    public function uploadImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->uploadImage($id, $request->file('image'));

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Imagem carregada com sucesso',
            'image_url' => $product->image_url
        ]);
    }

    public function uploadBaseImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->uploadBaseImage($id, $request->file('image'));

        return response()->json([
            'data' => new ProductResource($product),
            'message' => 'Imagem base carregada com sucesso',
            'base_image_url' => $product->base_image_url
        ]);
    }
}
