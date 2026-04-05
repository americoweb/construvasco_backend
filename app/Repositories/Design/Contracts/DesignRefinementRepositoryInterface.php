<?php

namespace App\Repositories\Design\Contracts;

use App\Models\Design\DesignRefinement;
use Illuminate\Database\Eloquent\Collection;

interface DesignRefinementRepositoryInterface
{
    public function findById(int $id): ?DesignRefinement;
    
    public function create(array $data): DesignRefinement;
    
    public function update(int $id, array $data): DesignRefinement;
    
    public function getByDesign(int $designId): Collection;
    
    public function getLatestByDesign(int $designId): ?DesignRefinement;
}
