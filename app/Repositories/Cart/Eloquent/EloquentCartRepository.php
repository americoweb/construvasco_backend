<?php

namespace App\Repositories\Cart\Eloquent;

use App\Models\Cart\Cart;
use App\Repositories\Cart\Contracts\CartRepositoryInterface;

class EloquentCartRepository implements CartRepositoryInterface
{
    public function __construct(
        protected Cart $model
    ) {}

    public function findById(int $id): ?Cart
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Cart
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByUser(int $userId): ?Cart
    {
        return $this->model->byUser($userId)->first();
    }

    public function findBySession(string $sessionId): ?Cart
    {
        return $this->model->bySession($sessionId)->first();
    }

    public function create(array $data): Cart
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Cart
    {
        $cart = $this->findById($id);
        $cart->update($data);
        return $cart->fresh();
    }

    public function delete(int $id): bool
    {
        $cart = $this->findById($id);
        return $cart->delete();
    }

    public function getActiveByUser(int $userId): ?Cart
    {
        return $this->model->byUser($userId)
            ->active()
            ->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->first();
    }

    public function getActiveBySession(string $sessionId): ?Cart
    {
        return $this->model->bySession($sessionId)
            ->active()
            ->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->first();
    }

    public function getWithItems(int $id): ?Cart
    {
        return $this->model->with('items.product', 'items.color', 'items.printArea', 'items.design')
            ->find($id);
    }

    public function clearExpired(): int
    {
        return $this->model->where('expires_at', '<', now())->delete();
    }
}
