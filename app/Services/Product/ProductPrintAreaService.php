<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use App\Models\Product\ProductPrintArea;
use Illuminate\Database\Eloquent\Collection;

class ProductPrintAreaService
{
    public function __construct(
        protected ProductPrintAreaRepositoryInterface $printAreaRepository
    ) {}

    public function getProductPrintAreas(int $productId): Collection
    {
        return $this->printAreaRepository->getByProduct($productId);
    }

    public function findById(int $id): ProductPrintArea
    {
        $area = $this->printAreaRepository->findById($id);
        
        if (!$area) {
            throw new \Exception('Área de impressão não encontrada');
        }
        
        return $area;
    }

    public function create(int $productId, array $data): ProductPrintArea
    {
        $data['product_id'] = $productId;
        
        return $this->printAreaRepository->create($data);
    }

    public function update(int $id, array $data): ProductPrintArea
    {
        return $this->printAreaRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->printAreaRepository->delete($id);
    }
}
