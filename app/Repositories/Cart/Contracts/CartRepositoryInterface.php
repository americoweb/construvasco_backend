<?php

namespace App\Repositories\Cart\Contracts;

use App\Models\Cart\Cart;

interface CartRepositoryInterface
{
    public function findById(int $id): ?Cart;
    
    public function findByUuid(string $uuid): ?Cart;
    
    public function findByUser(int $userId): ?Cart;
    
    public function findBySession(string $sessionId): ?Cart;
    
    public function create(array $data): Cart;
    
    public function update(int $id, array $data): Cart;
    
    public function delete(int $id): bool;
    
    public function getActiveByUser(int $userId): ?Cart;
    
    public function getActiveBySession(string $sessionId): ?Cart;
    
    public function getWithItems(int $id): ?Cart;
    
    public function clearExpired(): int;
}
