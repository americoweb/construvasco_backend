<?php

namespace App\Repositories\Product\Eloquent;

use App\Models\Product\ProductColor;
use App\Repositories\Product\Contracts\ProductColorRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentProductColorRepository implements ProductColorRepositoryInterface
{
    public function __construct(
        protected ProductColor $model
    ) {}

    public function findById(int $id): ?ProductColor
    {
        return $this->model->find($id);
    }

    public function getByProduct(int $productId): Collection
    {
        return $this->model->where('product_id', $productId)
            ->orderBy('sort_order')
            ->get();
    }

    public function create(array $data): ProductColor
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): ProductColor
    {
        $color = $this->findById($id);
        $color->update($data);
        return $color->fresh();
    }

    public function delete(int $id): bool
    {
        $color = $this->findById($id);
        return $color->delete();
    }

    public function updateStock(int $id, int $quantity): ProductColor
    {
        $color = $this->findById($id);
        $color->stock_quantity = $quantity;
        $color->save();
        return $color;
    }
}
