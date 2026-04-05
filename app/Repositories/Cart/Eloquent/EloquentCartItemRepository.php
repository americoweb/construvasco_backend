<?php

namespace App\Repositories\Cart\Eloquent;

use App\Models\Cart\CartItem;
use App\Repositories\Cart\Contracts\CartItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentCartItemRepository implements CartItemRepositoryInterface
{
    public function __construct(
        protected CartItem $model
    ) {}

    public function findById(int $id): ?CartItem
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?CartItem
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): CartItem
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): CartItem
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

    public function getByCart(int $cartId): Collection
    {
        return $this->model->where('cart_id', $cartId)
            ->with(['product', 'color', 'printArea', 'design'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function findInCart(int $cartId, int $productId, int $colorId, int $printAreaId): ?CartItem
    {
        return $this->model->where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->where('product_color_id', $colorId)
            ->where('product_print_area_id', $printAreaId)
            ->first();
    }

    public function deleteByCart(int $cartId): int
    {
        return $this->model->where('cart_id', $cartId)->delete();
    }
}
