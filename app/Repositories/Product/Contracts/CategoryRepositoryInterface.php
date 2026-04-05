<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\Category;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function all(array $filters = []): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function findById(int $id): ?Category;
    
    public function findByUuid(string $uuid): ?Category;
    
    public function findBySlug(string $slug): ?Category;
    
    public function create(array $data): Category;
    
    public function update(int $id, array $data): Category;
    
    public function delete(int $id): bool;
    
    public function getActive(): Collection;
    
    public function getRootCategories(): Collection;
}

