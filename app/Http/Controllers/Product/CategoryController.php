<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\CategoryService;
use App\Http\Requests\Product\CreateCategoryRequest;
use App\Http\Requests\Product\UpdateCategoryRequest;
use App\Http\Requests\Product\UploadImageRequest;
use App\Http\Resources\Product\CategoryResource;
use App\Http\Resources\Product\CategoryListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => CategoryListResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ]
        ]);
    }

    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Categoria criada com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);
        $category->load(['parent', 'children', 'products']);

        return response()->json([
            'data' => new CategoryResource($category)
        ]);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->update($id, $request->validated());

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Categoria atualizada com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->categoryService->delete($id);

        return response()->json([
            'message' => 'Categoria excluída com sucesso'
        ]);
    }

    public function active(): JsonResponse
    {
        $categories = $this->categoryService->getActive();

        return response()->json([
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function root(): JsonResponse
    {
        $categories = $this->categoryService->getRootCategories();

        return response()->json([
            'data' => CategoryResource::collection($categories)
        ]);
    }

    public function uploadImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->uploadImage($id, $request->file('image'));

        return response()->json([
            'data' => new CategoryResource($category),
            'message' => 'Imagem carregada com sucesso',
            'image_url' => $category->image_url
        ]);
    }
}

