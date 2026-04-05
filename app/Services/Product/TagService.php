<?php

namespace App\Services\Product;

use App\Repositories\Product\Contracts\TagRepositoryInterface;
use App\Models\Product\Tag;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function __construct(
        protected TagRepositoryInterface $tagRepository
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->tagRepository->paginate($filters, $perPage);
    }

    public function getAll(array $filters = []): Collection
    {
        return $this->tagRepository->all($filters);
    }

    public function findById(int $id): Tag
    {
        $tag = $this->tagRepository->findById($id);
        
        if (!$tag) {
            throw new \Exception('Tag não encontrada');
        }
        
        return $tag;
    }

    public function findByUuid(string $uuid): Tag
    {
        $tag = $this->tagRepository->findByUuid($uuid);
        
        if (!$tag) {
            throw new \Exception('Tag não encontrada');
        }
        
        return $tag;
    }

    public function findBySlug(string $slug): Tag
    {
        $tag = $this->tagRepository->findBySlug($slug);
        
        if (!$tag) {
            throw new \Exception('Tag não encontrada');
        }
        
        return $tag;
    }

    public function create(array $data): Tag
    {
        $data['slug'] = $this->generateSlug($data['name']);
        
        return $this->tagRepository->create($data);
    }

    public function update(int $id, array $data): Tag
    {
        if (isset($data['name'])) {
            $data['slug'] = $this->generateSlug($data['name'], $id);
        }
        
        return $this->tagRepository->update($id, $data);
    }

    public function delete(int $id): bool
    {
        return $this->tagRepository->delete($id);
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
        $tag = $this->tagRepository->findBySlug($slug);
        
        if (!$tag) {
            return false;
        }
        
        if ($excludeId && $tag->id === $excludeId) {
            return false;
        }
        
        return true;
    }
}

