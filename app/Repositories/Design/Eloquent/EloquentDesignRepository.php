<?php

namespace App\Repositories\Design\Eloquent;

use App\Models\Design\Design;
use App\Repositories\Design\Contracts\DesignRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentDesignRepository implements DesignRepositoryInterface
{
    public function __construct(
        protected Design $model
    ) {}

    public function findById(int $id): ?Design
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Design
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function create(array $data): Design
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Design
    {
        $design = $this->findById($id);
        $design->update($data);
        return $design->fresh();
    }

    public function delete(int $id): bool
    {
        $design = $this->findById($id);
        return $design->delete();
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->model->bySession($sessionId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->byUser($userId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->byUser($userId)
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getCompleted(): Collection
    {
        return $this->model->completed()
            ->with(['product', 'color', 'printArea'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getWithRelations(int $id): ?Design
    {
        return $this->model->with(['product', 'color', 'printArea', 'refinements'])
            ->find($id);
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['product', 'color', 'printArea']);

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['product_id'])) {
            $query->where('product_id', $filters['product_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('prompt', 'like', "%{$search}%")
                  ->orWhere('uuid', 'like', "%{$search}%");
            });
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
