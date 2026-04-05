<?php

namespace App\Repositories\Candidate\Contracts;

use App\Models\Candidate\Candidate;
use Illuminate\Pagination\LengthAwarePaginator;

interface CandidateRepositoryInterface
{
    public function find(int $id): ?Candidate;
    
    public function create(array $data): Candidate;
    
    public function update(Candidate $candidate, array $data): Candidate;
    
    public function delete(Candidate $candidate): bool;
    
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function search(array $criteria): array;
    
    public function advancedSearch(array $criteria): array;
}
