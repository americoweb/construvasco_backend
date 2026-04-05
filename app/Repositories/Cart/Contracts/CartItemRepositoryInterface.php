<?php

namespace App\Repositories\Cart\Contracts;

use App\Models\Cart\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemRepositoryInterface
{
    public function findById(int $id): ?CartItem;
    
    public function findByUuid(string $uuid): ?CartItem;
    
    public function create(array $data): CartItem;
    
    public function update(int $id, array $data): CartItem;
    
    public function delete(int $id): bool;
    
    public function getByCart(int $cartId): Collection;
    
    public function findInCart(int $cartId, int $productId, int $colorId, int $printAreaId): ?CartItem;
    
    public function deleteByCart(int $cartId): int;
}
