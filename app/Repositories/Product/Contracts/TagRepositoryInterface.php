<?php

namespace App\Repositories\Product\Contracts;

use App\Models\Product\Tag;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TagRepositoryInterface
{
    public function all(array $filters = []): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function findById(int $id): ?Tag;
    
    public function findByUuid(string $uuid): ?Tag;
    
    public function findBySlug(string $slug): ?Tag;
    
    public function create(array $data): Tag;
    
    public function update(int $id, array $data): Tag;
    
    public function delete(int $id): bool;
}

