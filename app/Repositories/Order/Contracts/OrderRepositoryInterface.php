<?php

namespace App\Repositories\Order\Contracts;

use App\Models\Order\Order;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    
    public function findByUuid(string $uuid): ?Order;
    
    public function findByOrderNumber(string $orderNumber): ?Order;
    
    public function create(array $data): Order;
    
    public function update(int $id, array $data): Order;
    
    public function delete(int $id): bool;
    
    public function getByUser(int $userId): Collection;
    
    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    public function getBySession(string $sessionId): Collection;
    
    public function getWithItems(int $id): ?Order;
    
    public function getPending(): Collection;
    
    public function getActive(): Collection;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
}
