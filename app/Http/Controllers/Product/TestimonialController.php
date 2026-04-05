<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Testimonial;
use App\Http\Requests\Product\CreateTestimonialRequest;
use App\Http\Requests\Product\UpdateTestimonialRequest;
use App\Http\Resources\Product\TestimonialResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class TestimonialController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Testimonial::query();

        // Filter by product if provided
        if ($request->has('product_id')) {
            $query->forProduct($request->get('product_id'));
        }

        // Filter active only if requested
        if ($request->boolean('active_only', false)) {
            $query->active();
        }

        // Order by sort_order
        $testimonials = $query->ordered()->get();

        return response()->json([
            'data' => TestimonialResource::collection($testimonials)
        ]);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->load('product');

            return response()->json([
                'data' => new TestimonialResource($testimonial)
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Depoimento não encontrado'
            ], 404);
        }
    }

    public function forProduct(int $productId): JsonResponse
    {
        $testimonials = Testimonial::forProduct($productId)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'data' => TestimonialResource::collection($testimonials)
        ]);
    }

    public function store(CreateTestimonialRequest $request): JsonResponse
    {
        $testimonial = Testimonial::create($request->validated());
        $testimonial->load('product');

        return response()->json([
            'data' => new TestimonialResource($testimonial),
            'message' => 'Depoimento criado com sucesso'
        ], 201);
    }

    public function update(UpdateTestimonialRequest $request, int $id): JsonResponse
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->update($request->validated());
            $testimonial->load('product');

            return response()->json([
                'data' => new TestimonialResource($testimonial),
                'message' => 'Depoimento atualizado com sucesso'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Depoimento não encontrado'
            ], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->delete();

            return response()->json([
                'message' => 'Depoimento excluído com sucesso'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Depoimento não encontrado'
            ], 404);
        }
    }

    public function toggleActive(int $id): JsonResponse
    {
        try {
            $testimonial = Testimonial::findOrFail($id);
            $testimonial->is_active = !$testimonial->is_active;
            $testimonial->save();
            $testimonial->load('product');

            return response()->json([
                'data' => new TestimonialResource($testimonial),
                'message' => $testimonial->is_active 
                    ? 'Depoimento ativado' 
                    : 'Depoimento desativado'
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Depoimento não encontrado'
            ], 404);
        }
    }
}

