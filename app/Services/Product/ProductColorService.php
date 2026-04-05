<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Collection;

class ProductColorService
{
    public function __construct(
        protected ProductColorRepositoryInterface $colorRepository
    ) {}

    public function getProductColors(int $productId): Collection
    {
        return $this->colorRepository->getByProduct($productId);
    }

    public function findById(int $id): ProductColor
    {
        $color = $this->colorRepository->findById($id);
        
        if (!$color) {
            throw new \Exception('Cor não encontrada');
        }
        
        return $color;
    }

    public function create(int $productId, array $data): ProductColor
    {
        $data['product_id'] = $productId;
        
        return $this->colorRepository->create($data);
    }

    public function update(int $id, array $data): ProductColor
    {
        return $this->colorRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->colorRepository->delete($id);
    }

    public function updateStock(int $id, int $quantity): ProductColor
    {
        return $this->colorRepository->updateStock($id, $quantity);
    }

    public function decrementStock(int $id, int $quantity): ProductColor
    {
        $color = $this->findById($id);
        
        if ($color->stock_quantity !== null) {
            $newQuantity = max(0, $color->stock_quantity - $quantity);
            return $this->colorRepository->updateStock($id, $newQuantity);
        }
        
        return $color;
    }

    public function checkAvailability(int $id, int $requiredQuantity): bool
    {
        $color = $this->findById($id);
        return $color->hasStock($requiredQuantity);
    }
}
