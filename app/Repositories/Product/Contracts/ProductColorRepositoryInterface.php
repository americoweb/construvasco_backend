<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\ProductColor;
use Illuminate\Database\Eloquent\Collection;

interface ProductColorRepositoryInterface
{
    public function findById(int $id): ?ProductColor;
    
    public function getByProduct(int $productId): Collection;
    
    public function create(array $data): ProductColor;
    
    public function update(int $id, array $data): ProductColor;
    
    public function delete(int $id): bool;
    
    public function updateStock(int $id, int $quantity): ProductColor;
}
