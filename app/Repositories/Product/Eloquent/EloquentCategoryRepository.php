<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\Category;
use App\Repositories\Product\Contracts\CategoryRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        protected Category $model
    ) {}

    public function all(array $filters = []): Collection
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->with('parent')
            ->withCount('products')
            ->ordered()
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->with('parent')
            ->withCount('products')
            ->ordered()
            ->paginate($perPage);
    }

    public function findById(int $id): ?Category
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Category
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?Category
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Category
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Category
    {
        $category = $this->findById($id);
        $category->update($data);
        return $category->fresh();
    }

    public function delete(int $id): bool
    {
        $category = $this->findById($id);
        return $category->delete();
    }

    public function getActive(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getRootCategories(): Collection
    {
        return $this->model->root()->active()->ordered()->with('children')->get();
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['parent_id'])) {
            $query->where('parent_id', $filters['parent_id']);
        }

        if (isset($filters['root_only']) && $filters['root_only']) {
            $query->whereNull('parent_id');
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
    }
}

