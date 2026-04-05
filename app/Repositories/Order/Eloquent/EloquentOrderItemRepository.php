<?php

namespace App\Repositories\Order\Eloquent;

use App\Models\Order\OrderItem;
use App\Repositories\Order\Contracts\OrderItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderItemRepository implements OrderItemRepositoryInterface
{
    public function __construct(
        protected OrderItem $model
    ) {}

    public function findById(int $id): ?OrderItem
    {
        return $this->model->find($id);
    }

    public function create(array $data): OrderItem
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): OrderItem
    {
        $item = $this->findById($id);
        $item->update($data);
        return $item->fresh();
    }

    public function delete(int $id): bool
    {
        $item = $this->findById($id);
        return $item->delete();
    }

    public function getByOrder(int $orderId): Collection
    {
        return $this->model->where('order_id', $orderId)->get();
    }

    public function createMany(array $items): Collection
    {
        $created = collect();
        
        foreach ($items as $itemData) {
            $created->push($this->create($itemData));
        }
        
        return $created;
    }
}
