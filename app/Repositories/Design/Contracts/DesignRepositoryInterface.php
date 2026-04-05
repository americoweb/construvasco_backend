<?php

namespace App\Repositories\Design\Contracts;

use App\Models\Design\Design;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DesignRepositoryInterface
{
    public function findById(int $id): ?Design;
    
    public function findByUuid(string $uuid): ?Design;
    
    public function create(array $data): Design;
    
    public function update(int $id, array $data): Design;
    
    public function delete(int $id): bool;
    
    public function getBySession(string $sessionId): Collection;
    
    public function getByUser(int $userId): Collection;
    
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    public function getCompleted(): Collection;
    
    public function getWithRelations(int $id): ?Design;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
