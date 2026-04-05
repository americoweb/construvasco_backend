<?php

namespace App\Repositories\Order\Contracts;

use App\Models\Order\OrderItem;
use Illuminate\Database\Eloquent\Collection;

interface OrderItemRepositoryInterface
{
    public function findById(int $id): ?OrderItem;
    
    public function create(array $data): OrderItem;
    
    public function update(int $id, array $data): OrderItem;
    
    public function delete(int $id): bool;
    
    public function getByOrder(int $orderId): Collection;
    
    public function createMany(array $items): Collection;
}
