<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\ProductPrintArea;
use App\Repositories\Product\Contracts\ProductPrintAreaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductPrintAreaRepository implements ProductPrintAreaRepositoryInterface
{
    public function __construct(
        protected ProductPrintArea $model
    ) {}

    public function findById(int $id): ?ProductPrintArea
    {
        return $this->model->find($id);
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): ProductPrintArea
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ProductPrintArea
    {
        $area = $this->findById($id);
        $area->update($data);
        return $area->fresh();
    }

    public function delete(int $id): bool
    {
        $area = $this->findById($id);
        return $area->delete();
    }
}
