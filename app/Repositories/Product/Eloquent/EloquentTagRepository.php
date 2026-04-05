<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\Tag;
use App\Repositories\Product\Contracts\TagRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentTagRepository implements TagRepositoryInterface
{
    public function __construct(
        protected Tag $model
    ) {}

    public function all(array $filters = []): Collection
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('name')->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('name')->paginate($perPage);
    }

    public function findById(int $id): ?Tag
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Tag
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?Tag
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Tag
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Tag
    {
        $tag = $this->findById($id);
        $tag->update($data);
        return $tag->fresh();
    }

    public function delete(int $id): bool
    {
        $tag = $this->findById($id);
        return $tag->delete();
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%");
            });
        }
    }
}

