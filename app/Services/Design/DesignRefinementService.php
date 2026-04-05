<?php

namespace App\Services\Design;

use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use App\Models\Design\DesignRefinement;
use App\Enums\Design\DesignStatus;
use Illuminate\Database\Eloquent\Collection;

class DesignRefinementService
{
    public function __construct(
        protected DesignRefinementRepositoryInterface $refinementRepository,
        protected DesignRepositoryInterface $designRepository
    ) {}

    public function findById(int $id): DesignRefinement
    {
        $refinement = $this->refinementRepository->findById($id);
        
        if (!$refinement) {
            throw new \Exception('Refinamento não encontrado');
        }
        
        return $refinement;
    }

    public function createRefinement(int $designId, array $data): DesignRefinement
    {
        $design = $this->designRepository->findById($designId);
        
        if (!$design) {
            throw new \Exception('Design não encontrado');
        }
        
        $data['design_id'] = $designId;
        $data['previous_mockup_url'] = $design->mockup_url;
        
        if (!isset($data['status'])) {
            $data['status'] = DesignStatus::GENERATING;
        }
        
        $refinement = $this->refinementRepository->create($data);
        
        // Update parent design status
        $this->designRepository->update($designId, [
            'status' => DesignStatus::REFINED
        ]);
        
        return $refinement;
    }

    public function getDesignRefinements(int $designId): Collection
    {
        return $this->refinementRepository->getByDesign($designId);
    }

    public function getLatestRefinement(int $designId): ?DesignRefinement
    {
        return $this->refinementRepository->getLatestByDesign($designId);
    }

    public function saveRefinementMockup(int $id, string $mockupUrl = null, string $mockupBase64 = null): DesignRefinement
    {
        $data = ['status' => DesignStatus::COMPLETED];
        
        if ($mockupUrl) {
            $data['new_mockup_url'] = $mockupUrl;
        }
        
        if ($mockupBase64) {
            $data['new_mockup_base64'] = $mockupBase64;
        }
        
        $refinement = $this->refinementRepository->update($id, $data);
        
        // Also update the parent design mockup
        $this->designRepository->update($refinement->design_id, [
            'mockup_url' => $mockupUrl,
            'mockup_base64' => $mockupBase64,
        ]);
        
        return $refinement;
    }

    public function markAsFailed(int $id, array $metadata = []): DesignRefinement
    {
        return $this->refinementRepository->update($id, [
            'status' => DesignStatus::FAILED,
            'ai_response_metadata' => $metadata,
        ]);
    }
}
