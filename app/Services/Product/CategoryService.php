<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\CategoryRepositoryInterface;
use App\Models\Product\Category;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CategoryService
{
    public function __construct(
        protected CategoryRepositoryInterface $categoryRepository
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->categoryRepository->paginate($filters, $perPage);
    }

    public function getAll(array $filters = []): Collection
    {
        return $this->categoryRepository->all($filters);
    }

    public function findById(int $id): Category
    {
        $category = $this->categoryRepository->findById($id);
        
        if (!$category) {
            throw new \Exception('Categoria não encontrada');
        }
        
        return $category;
    }

    public function findByUuid(string $uuid): Category
    {
        $category = $this->categoryRepository->findByUuid($uuid);
        
        if (!$category) {
            throw new \Exception('Categoria não encontrada');
        }
        
        return $category;
    }

    public function findBySlug(string $slug): Category
    {
        $category = $this->categoryRepository->findBySlug($slug);
        
        if (!$category) {
            throw new \Exception('Categoria não encontrada');
        }
        
        return $category;
    }

    public function create(array $data): Category
    {
        $data['slug'] = $this->generateSlug($data['name']);
        
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }
        
        return $this->categoryRepository->create($data);
    }

    public function update(int $id, array $data): Category
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name'], $id);
        }
        
        return $this->categoryRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->categoryRepository->delete($id);
    }

    public function getActive(): Collection
    {
        return $this->categoryRepository->getActive();
    }

    public function getRootCategories(): Collection
    {
        return $this->categoryRepository->getRootCategories();
    }

    public function uploadImage(int $categoryId, UploadedFile $file): Category
    {
        $category = $this->findById($categoryId);
        
        // Delete old image if exists
        if ($category->image_url) {
            // image_url is stored as relative path, but handle legacy full URLs too
            $oldPath = $this->extractPathFromUrl($category->image_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        
        $path = $file->store('categories/images', 'public');
        // Store only the relative path (without 'public/' prefix) in database
        // The full URL will be generated dynamically in the Resource
        $relativePath = str_replace('public/', '', $path);
        
        return $this->categoryRepository->update($categoryId, [
            'image_url' => $relativePath
        ]);
    }

    protected function extractPathFromUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        
        // If it's already a relative path, return as is
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return $url;
        }
        
        // Extract path from storage URL
        // Example: http://domain.com/storage/categories/images/file.jpg -> categories/images/file.jpg
        if (strpos($url, '/storage/') !== false) {
            return substr($url, strpos($url, '/storage/') + 9);
        }
        return null;
    }

    protected function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    protected function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $category = $this->categoryRepository->findBySlug($slug);
        
        if (!$category) {
            return false;
        }
        
        if ($excludeId && $category->id === $excludeId) {
            return false;
        }
        
        return true;
    }
}

