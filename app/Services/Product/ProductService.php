<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\ProductRepositoryInterface;
use App\Models\Product\Product;
use App\Enums\Product\ProductStatus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->productRepository->paginate($filters, $perPage);
    }

    public function getAll(array $filters = []): Collection
    {
        return $this->productRepository->all($filters);
    }

    public function findById(int $id): Product
    {
        $product = $this->productRepository->findById($id);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function findByUuid(string $uuid): Product
    {
        $product = $this->productRepository->findByUuid($uuid);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function findBySlug(string $slug): Product
    {
        $product = $this->productRepository->findBySlug($slug);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        return $product;
    }

    public function create(array $data): Product
    {
        $categoryIds = $data['category_ids'] ?? [];
        $tagIds = $data['tag_ids'] ?? [];
        unset($data['category_ids'], $data['tag_ids']);

        $data['slug'] = $this->generateSlug($data['name']);
        
        if (!isset($data['status'])) {
            $data['status'] = ProductStatus::ACTIVE;
        }
        
        $product = $this->productRepository->create($data);

        // Sync categories and tags
        if (!empty($categoryIds)) {
            $product->categories()->sync($categoryIds);
        }
        if (!empty($tagIds)) {
            $product->tags()->sync($tagIds);
        }

        return $product->fresh(['categories', 'tags']);
    }

    public function update(int $id, array $data): Product
    {
        $categoryIds = $data['category_ids'] ?? null;
        $tagIds = $data['tag_ids'] ?? null;
        unset($data['category_ids'], $data['tag_ids']);

        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name'], $id);
        }
        
        $product = $this->productRepository->update($id, $data);

        // Sync categories and tags if provided
        if ($categoryIds !== null) {
            $product->categories()->sync($categoryIds);
        }
        if ($tagIds !== null) {
            $product->tags()->sync($tagIds);
        }

        return $product->fresh(['categories', 'tags']);
    }

    public function delete(int $id): bool
    {
        return $this->productRepository->delete($id);
    }

    public function getActiveProducts(array $filters = []): Collection
    {
        $products = $this->productRepository->getActive($filters);
        
        // Load categories for each product for display purposes
        $products->load('categories');
        
        return $products;
    }

    public function getFeaturedProducts(): Collection
    {
        return $this->productRepository->getFeatured();
    }

    public function getProductWithDetails(int $id): Product
    {
        $product = $this->productRepository->getWithColorsAndAreas($id);
        
        if (!$product) {
            throw new \Exception('Produto não encontrado');
        }
        
        $product->load(['categories.parent', 'tags']);
        
        return $product;
    }

    public function calculateOrderTotal(int $productId, int $quantity): array
    {
        $product = $this->findById($productId);
        
        $effectiveQuantity = max($quantity, $product->min_quantity);
        $totalPrice = $product->price * $effectiveQuantity;
        
        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => $effectiveQuantity,
            'min_quantity' => $product->min_quantity,
            'total_price' => $totalPrice,
            'currency' => 'MT',
        ];
    }

    public function updateStatus(int $id, ProductStatus $status): Product
    {
        return $this->productRepository->update($id, ['status' => $status]);
    }

    public function toggleFeatured(int $id): Product
    {
        $product = $this->findById($id);
        
        return $this->productRepository->update($id, [
            'is_featured' => !$product->is_featured
        ]);
    }

    public function uploadImage(int $productId, UploadedFile $file): Product
    {
        $product = $this->findById($productId);
        
        // Delete old image if exists
        if ($product->image_url) {
            // image_url is stored as relative path, but handle legacy full URLs too
            $oldPath = $this->extractPathFromUrl($product->image_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        
        $path = $file->store('products/images', 'public');
        // Store only the relative path (without 'public/' prefix) in database
        // The full URL will be generated dynamically in the Resource
        $relativePath = str_replace('public/', '', $path);
        
        return $this->productRepository->update($productId, [
            'image_url' => $relativePath
        ]);
    }

    public function uploadBaseImage(int $productId, UploadedFile $file): Product
    {
        $product = $this->findById($productId);
        
        // Delete old base image if exists
        if ($product->base_image_url) {
            // base_image_url is stored as relative path, but handle legacy full URLs too
            $oldPath = $this->extractPathFromUrl($product->base_image_url);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }
        
        $path = $file->store('products/base-images', 'public');
        // Store only the relative path (without 'public/' prefix) in database
        // The full URL will be generated dynamically in the Resource
        $relativePath = str_replace('public/', '', $path);
        
        return $this->productRepository->update($productId, [
            'base_image_url' => $relativePath
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
        // Example: http://domain.com/storage/products/images/file.jpg -> products/images/file.jpg
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
        $product = $this->productRepository->findBySlug($slug);
        
        if (!$product) {
            return false;
        }
        
        if ($excludeId && $product->id === $excludeId) {
            return false;
        }
        
        return true;
    }
}
