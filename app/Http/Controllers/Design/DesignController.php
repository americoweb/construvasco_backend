<?php

namespace App\Http\Controllers\Design;

use App\Http\Controllers\Controller;
use App\Services\Design\DesignService;
use App\Http\Requests\Design\CreateDesignRequest;
use App\Http\Requests\Design\UpdateDesignRequest;
use App\Http\Requests\Design\UploadImageRequest;
use App\Http\Resources\Design\DesignResource;
use App\Http\Resources\Design\DesignListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DesignController extends Controller
{
    public function __construct(
        protected DesignService $designService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'status' => $request->get('status'),
            'product_id' => $request->get('product_id'),
            'user_id' => $request->get('user_id'),
            'search' => $request->get('search'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $designs = $this->designService->paginate(
            $filters,
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => DesignListResource::collection($designs->items()),
            'meta' => [
                'current_page' => $designs->currentPage(),
                'last_page' => $designs->lastPage(),
                'per_page' => $designs->perPage(),
                'total' => $designs->total(),
            ]
        ]);
    }

    public function store(CreateDesignRequest $request): JsonResponse
    {
        $design = $this->designService->create($request->validated());

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design criado com sucesso'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $design = $this->designService->getDesignWithDetails($id);

        return response()->json([
            'data' => new DesignResource($design)
        ]);
    }

    public function showByUuid(string $uuid): JsonResponse
    {
        $design = $this->designService->findByUuid($uuid);
        $design->load(['product', 'color', 'printArea', 'refinements']);

        return response()->json([
            'data' => new DesignResource($design)
        ]);
    }

    public function update(UpdateDesignRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->update($id, $request->validated());

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design atualizado com sucesso'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->designService->delete($id);

        return response()->json([
            'message' => 'Design excluído com sucesso'
        ]);
    }

    public function getBySession(string $sessionId): JsonResponse
    {
        $designs = $this->designService->getBySession($sessionId);

        return response()->json([
            'data' => DesignListResource::collection($designs)
        ]);
    }

    public function getByUser(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        
        $designs = $this->designService->paginateByUser(
            $userId,
            $request->get('per_page', 15)
        );

        return response()->json([
            'data' => DesignListResource::collection($designs->items()),
            'meta' => [
                'current_page' => $designs->currentPage(),
                'last_page' => $designs->lastPage(),
                'per_page' => $designs->perPage(),
                'total' => $designs->total(),
            ]
        ]);
    }

    public function uploadLogo(UploadImageRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->uploadLogo($id, $request->file('image'));

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Logo carregado com sucesso'
        ]);
    }

    public function uploadReferenceImage(UploadImageRequest $request, int $id): JsonResponse
    {
        $design = $this->designService->uploadReferenceImage($id, $request->file('image'));

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Imagem de referência carregada com sucesso'
        ]);
    }

    public function removeLogo(int $id): JsonResponse
    {
        $design = $this->designService->removeLogo($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Logo removido com sucesso'
        ]);
    }

    public function removeReferenceImage(int $id): JsonResponse
    {
        $design = $this->designService->removeReferenceImage($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Imagem de referência removida com sucesso'
        ]);
    }

    public function saveMockup(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'mockup_url' => 'nullable|url|max:1000',
            'mockup_base64' => 'nullable|string',
        ]);

        $design = $this->designService->saveMockup(
            $id,
            $request->get('mockup_url'),
            $request->get('mockup_base64')
        );

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Mockup salvo com sucesso'
        ]);
    }

    public function markAsGenerating(int $id): JsonResponse
    {
        $design = $this->designService->markAsGenerating($id);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design marcado como gerando'
        ]);
    }

    public function markAsFailed(Request $request, int $id): JsonResponse
    {
        $metadata = $request->get('metadata', []);
        $design = $this->designService->markAsFailed($id, $metadata);

        return response()->json([
            'data' => new DesignResource($design),
            'message' => 'Design marcado como falha'
        ]);
    }

    public function getLogoBase64(int $id): JsonResponse
    {
        $logoData = $this->designService->getLogoAsBase64($id);

        if (!$logoData) {
            return response()->json([
                'message' => 'Logo não encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $logoData
        ]);
    }

    public function getReferenceImageBase64(int $id): JsonResponse
    {
        $imageData = $this->designService->getReferenceImageAsBase64($id);

        if (!$imageData) {
            return response()->json([
                'message' => 'Imagem de referência não encontrada'
            ], 404);
        }

        return response()->json([
            'data' => $imageData
        ]);
    }

    /**
     * Generate mockup from existing design (auto-includes logo and reference)
     */
    public function generateMockup(int $id): JsonResponse
    {
        try {
            $mockupUrl = $this->designService->generateMockupFromDesign($id);
            $design = $this->designService->getDesignWithDetails($id);

            return response()->json([
                'data' => new DesignResource($design),
                'message' => 'Mockup gerado com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar mockup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download logo file for printing
     */
    public function downloadLogo(int $id): BinaryFileResponse|JsonResponse
    {
        try {
            $logo = $this->designService->downloadLogo($id);
            
            return response()->download(
                $logo['path'],
                $logo['filename'],
                [
                    'Content-Type' => $logo['mime_type'],
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Download reference image file for printing
     */
    public function downloadReferenceImage(int $id): BinaryFileResponse|JsonResponse
    {
        try {
            $reference = $this->designService->downloadReferenceImage($id);
            
            return response()->download(
                $reference['path'],
                $reference['filename'],
                [
                    'Content-Type' => $reference['mime_type'],
                ]
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get all design files for printing (returns URLs and specifications)
     */
    public function getDesignFilesForPrinting(int $id): JsonResponse
    {
        try {
            $files = $this->designService->getDesignFilesForPrinting($id);

            return response()->json([
                'data' => $files,
                'message' => 'Arquivos do design recuperados com sucesso'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao recuperar arquivos: ' . $e->getMessage()
            ], 500);
        }
    }
}
