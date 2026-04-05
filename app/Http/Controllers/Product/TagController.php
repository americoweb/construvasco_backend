<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Services\Product\TagService;
use App\Http\Requests\Product\CreateTagRequest;
use App\Http\Requests\Product\UpdateTagRequest;
use App\Http\Resources\Product\TagResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    public function __construct(
        protected TagService $tagService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tags = $this->tagService->list(
            $request->all(),
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => TagResource::collection($tags->items()),
            'meta' => [
                'current_page' => $tags->currentPage(),
                'last_page' => $tags->lastPage(),
                'per_page' => $tags->perPage(),
                'total' => $tags->total(),
            ]
        ]);
    }

    public function store(CreateTagRequest $request): JsonResponse
    {
        $tag = $this->tagService->create($request->validated());

        return response()->json([
            'data' => new TagResource($tag),
            'message' => 'Tag criada com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $tag = $this->tagService->findById($id);
        $tag->load('products');

        return response()->json([
            'data' => new TagResource($tag)
        ]);
    }

    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = $this->tagService->update($id, $request->validated());

        return response()->json([
            'data' => new TagResource($tag),
            'message' => 'Tag atualizada com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->tagService->delete($id);

        return response()->json([
            'message' => 'Tag excluída com sucesso'
        ]);
    }

    public function all(): JsonResponse
    {
        $tags = $this->tagService->getAll();

        return response()->json([
            'data' => TagResource::collection($tags)
        ]);
    }
}

