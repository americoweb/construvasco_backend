<?php

namespace App\Repositories\Design\Eloquent;

use App\Models\Design\DesignRefinement;
use App\Repositories\Design\Contracts\DesignRefinementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EloquentDesignRefinementRepository implements DesignRefinementRepositoryInterface
{
    public function __construct(
        protected DesignRefinement $model
    ) {}

    public function findById(int $id): ?DesignRefinement
    {
        return $this->model->find($id);
    }

    public function create(array $data): DesignRefinement
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): DesignRefinement
    {
        $refinement = $this->findById($id);
        $refinement->update($data);
        return $refinement->fresh();
    }

    public function getByDesign(int $designId): Collection
    {
        return $this->model->where('design_id', $designId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestByDesign(int $designId): ?DesignRefinement
    {
        return $this->model->where('design_id', $designId)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
