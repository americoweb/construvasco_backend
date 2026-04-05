<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function all(array $filters = []): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function findById(int $id): ?Product;
    
    public function findByUuid(string $uuid): ?Product;
    
    public function findBySlug(string $slug): ?Product;
    
    public function create(array $data): Product;
    
    public function update(int $id, array $data): Product;
    
    public function delete(int $id): bool;
    
    public function getActive(array $filters = []): Collection;
    
    public function getFeatured(): Collection;
    
    public function getWithColorsAndAreas(int $id): ?Product;
}
