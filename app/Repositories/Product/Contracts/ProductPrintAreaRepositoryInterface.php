<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\ProductPrintArea;
use Illuminate\Database\Eloquent\Collection;

interface ProductPrintAreaRepositoryInterface
{
    public function findById(int $id): ?ProductPrintArea;
    
    public function getByProduct(int $productId): Collection;
    
    public function create(array $data): ProductPrintArea;
    
    public function update(int $id, array $data): ProductPrintArea;
    
    public function delete(int $id): bool;
}
