<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\Product;
use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Enums\Product\ProductStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        protected Product $model
    ) {}

    public function all(array $filters = []): Collection
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->ordered()->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->query();
        
        $this->applyFilters($query, $filters);
        
        return $query->ordered()->paginate($perPage);
    }

    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Product
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }

    public function create(array $data): Product
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Product
    {
        $product = $this->findById($id);
        $product->update($data);
        return $product->fresh();
    }

    public function delete(int $id): bool
    {
        $product = $this->findById($id);
        return $product->delete();
    }

    public function getActive(array $filters = []): Collection
    {
        $query = $this->model->active();
        
        // Filter by category slug if provided
        if (!empty($filters['category'])) {
            $categorySlug = $filters['category'];
            
            // First, find the category to check if it has children
            $category = \App\Models\Product\Category::with('children')->where('slug', $categorySlug)->first();
            
            if ($category) {
                // Get all category IDs to filter (parent + all children)
                $categoryIds = [$category->id];
                
                // If category has children, include them in the filter
                if ($category->children && $category->children->count() > 0) {
                    $childIds = $category->children->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $childIds);
                }
                
                // Filter products that belong to this category or any of its children
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('category_product.category_id', $categoryIds);
                });
            } else {
                // Category not found, return empty result
                $query->whereRaw('1 = 0');
            }
        }
        
        // Filter by subcategory slug if provided
        if (!empty($filters['subcategory'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('slug', $filters['subcategory']);
            });
        }
        
        // Apply other filters
        $this->applyFilters($query, $filters);
        
        return $query->ordered()->get();
    }

    public function getFeatured(): Collection
    {
        return $this->model->active()->featured()->ordered()->get();
    }

    public function getWithColorsAndAreas(int $id): ?Product
    {
        return $this->model->with(['activeColors', 'activePrintAreas'])->find($id);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
    }
}
