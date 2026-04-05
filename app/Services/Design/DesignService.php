<?php

namespace App\Services\Design;

use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Models\Design\Design;
use App\Enums\Design\DesignStatus;
use App\Services\AI\SuggestionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DesignService
{
    public function __construct(
        protected DesignRepositoryInterface $designRepository,
        protected ?SuggestionService $suggestionService = null
    ) {}

    public function findById(int $id): Design
    {
        $design = $this->designRepository->findById($id);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function findByUuid(string $uuid): Design
    {
        $design = $this->designRepository->findByUuid($uuid);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function create(array $data): Design
    {
        if (!isset($data['status'])) {
            $data['status'] = DesignStatus::DRAFT;
        }
        
        if (!isset($data['generation_attempts'])) {
            $data['generation_attempts'] = 0;
        }
        
        return $this->designRepository->create($data);
    }

    public function update(int $id, array $data): Design
    {
        return $this->designRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        $design = $this->findById($id);
        
        // Clean up files
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        return $this->designRepository->delete($id);
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->designRepository->getBySession($sessionId);
    }

    public function getByUser(int $userId): Collection
    {
        return $this->designRepository->getByUser($userId);
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->designRepository->paginateByUser($userId, $perPage);
    }

    public function getDesignWithDetails(int $id): Design
    {
        $design = $this->designRepository->getWithRelations($id);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        return $design;
    }

    public function uploadLogo(int $designId, UploadedFile $file): Design
    {
        $design = $this->findById($designId);
        
        // Delete old logo if exists
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        $path = $file->store('logos', 'public');
        
        return $this->designRepository->update($designId, [
            'logo_path' => $path,
            'logo_mime_type' => $file->getMimeType(),
        ]);
    }

    public function uploadReferenceImage(int $designId, UploadedFile $file): Design
    {
        $design = $this->findById($designId);
        
        // Delete old reference if exists
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        $path = $file->store('references', 'public');
        
        return $this->designRepository->update($designId, [
            'reference_image_path' => $path,
            'reference_mime_type' => $file->getMimeType(),
        ]);
    }

    public function removeLogo(int $designId): Design
    {
        $design = $this->findById($designId);
        
        if ($design->logo_path) {
            Storage::disk('public')->delete($design->logo_path);
        }
        
        return $this->designRepository->update($designId, [
            'logo_path' => null,
            'logo_mime_type' => null,
        ]);
    }

    public function removeReferenceImage(int $designId): Design
    {
        $design = $this->findById($designId);
        
        if ($design->reference_image_path) {
            Storage::disk('public')->delete($design->reference_image_path);
        }
        
        return $this->designRepository->update($designId, [
            'reference_image_path' => null,
            'reference_mime_type' => null,
        ]);
    }

    public function updateStatus(int $id, DesignStatus $status): Design
    {
        return $this->designRepository->update($id, ['status' => $status]);
    }

    public function saveMockup(int $id, string $mockupUrl = null, string $mockupBase64 = null): Design
    {
        $data = ['status' => DesignStatus::COMPLETED];
        
        if ($mockupUrl) {
            $data['mockup_url'] = $mockupUrl;
        }
        
        if ($mockupBase64) {
            $data['mockup_base64'] = $mockupBase64;
        }
        
        return $this->designRepository->update($id, $data);
    }

    public function markAsGenerating(int $id): Design
    {
        $design = $this->findById($id);
        $design->incrementGenerationAttempts();
        
        return $this->designRepository->update($id, [
            'status' => DesignStatus::GENERATING
        ]);
    }

    public function markAsFailed(int $id, array $metadata = []): Design
    {
        return $this->designRepository->update($id, [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => $metadata,
        ]);
    }

    public function getLogoAsBase64(int $designId): ?array
    {
        $design = $this->findById($designId);
        
        if (!$design->logo_path) {
            return null;
        }
        
        $content = Storage::disk('public')->get($design->logo_path);
        
        return [
            'data' => base64_encode($content),
            'mime_type' => $design->logo_mime_type,
        ];
    }

    public function getReferenceImageAsBase64(int $designId): ?array
    {
        $design = $this->findById($designId);
        
        if (!$design->reference_image_path) {
            return null;
        }
        
        $content = Storage::disk('public')->get($design->reference_image_path);
        
        return [
            'data' => base64_encode($content),
            'mime_type' => $design->reference_mime_type,
        ];
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->designRepository->paginate($filters, $perPage);
    }

    /**
     * Generate mockup from existing design, automatically including logo and reference image
     */
    public function generateMockupFromDesign(int $designId): string
    {
        $design = $this->getDesignWithDetails($designId);
        
        if (!$design->product_id) {
            throw new \Exception('Design não possui produto associado');
        }

        // Prepare design data with logo and reference from design
        $designData = [
            'design_prompt' => $design->prompt,
        ];

        // Get logo as base64 if exists
        $logoData = $this->getLogoAsBase64($designId);
        if ($logoData) {
            $designData['logo_base64'] = $logoData['data'];
            $designData['logo_mime_type'] = $logoData['mime_type'];
        }

        // Get reference image as base64 if exists
        $referenceData = $this->getReferenceImageAsBase64($designId);
        if ($referenceData) {
            $designData['reference_image_base64'] = $referenceData['data'];
            $designData['reference_image_mime_type'] = $referenceData['mime_type'];
        }

        if (!$this->suggestionService) {
            $this->suggestionService = app(SuggestionService::class);
        }

        // Generate mockup
        $mockupPath = $this->suggestionService->generateMockup($design->product_id, $designData);
        
        // Convert to full URL
        $baseUrl = request()->getSchemeAndHttpHost();
        $mockupUrl = rtrim($baseUrl, '/') . '/storage/' . ltrim($mockupPath, '/');
        
        // Save mockup to design
        $this->saveMockup($designId, $mockupUrl);
        
        return $mockupUrl;
    }

    /**
     * Download logo file for printing
     */
    public function downloadLogo(int $designId)
    {
        $design = $this->findById($designId);
        
        if (!$design->logo_path) {
            throw new \Exception('Logo não encontrado');
        }

        $path = Storage::disk('public')->path($design->logo_path);
        
        if (!file_exists($path)) {
            throw new \Exception('Arquivo de logo não encontrado');
        }

        return [
            'path' => $path,
            'filename' => 'logo_' . $design->id . '.' . pathinfo($path, PATHINFO_EXTENSION),
            'mime_type' => $design->logo_mime_type ?? 'image/png',
        ];
    }

    /**
     * Download reference image file for printing
     */
    public function downloadReferenceImage(int $designId)
    {
        $design = $this->findById($designId);
        
        if (!$design->reference_image_path) {
            throw new \Exception('Imagem de referência não encontrada');
        }

        $path = Storage::disk('public')->path($design->reference_image_path);
        
        if (!file_exists($path)) {
            throw new \Exception('Arquivo de imagem de referência não encontrado');
        }

        return [
            'path' => $path,
            'filename' => 'reference_' . $design->id . '.' . pathinfo($path, PATHINFO_EXTENSION),
            'mime_type' => $design->reference_mime_type ?? 'image/png',
        ];
    }

    /**
     * Get all design files for printing (logo, reference, mockup)
     */
    public function getDesignFilesForPrinting(int $designId): array
    {
        $design = $this->getDesignWithDetails($designId);
        $files = [];

        // Logo file
        if ($design->logo_path) {
            try {
                $logo = $this->downloadLogo($designId);
                $files['logo'] = [
                    'url' => Storage::disk('public')->url($design->logo_path),
                    'filename' => $logo['filename'],
                    'mime_type' => $logo['mime_type'],
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to get logo file', ['design_id' => $designId, 'error' => $e->getMessage()]);
            }
        }

        // Reference image file
        if ($design->reference_image_path) {
            try {
                $reference = $this->downloadReferenceImage($designId);
                $files['reference'] = [
                    'url' => Storage::disk('public')->url($design->reference_image_path),
                    'filename' => $reference['filename'],
                    'mime_type' => $reference['mime_type'],
                ];
            } catch (\Exception $e) {
                Log::warning('Failed to get reference image file', ['design_id' => $designId, 'error' => $e->getMessage()]);
            }
        }

        // Mockup file (if available)
        if ($design->mockup_url) {
            $files['mockup'] = [
                'url' => $design->mockup_url,
                'filename' => 'mockup_' . $design->id . '.png',
                'mime_type' => 'image/png',
            ];
        }

        // Design specifications
        $files['specifications'] = [
            'product' => $design->product ? [
                'id' => $design->product->id,
                'name' => $design->product->name,
            ] : null,
            'color' => $design->color ? [
                'id' => $design->color->id,
                'name' => $design->color->name,
                'hex_code' => $design->color->hex_code,
            ] : null,
            'print_area' => $design->printArea ? [
                'id' => $design->printArea->id,
                'name' => $design->printArea->name,
                'position' => $design->printArea->position,
                'max_width_cm' => $design->printArea->max_width_cm,
                'max_height_cm' => $design->printArea->max_height_cm,
            ] : null,
            'prompt' => $design->prompt,
        ];

        return $files;
    }
}
