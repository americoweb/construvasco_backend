<?php

namespace App\Repositories\Candidate;

use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;
use App\Models\Candidate\Candidate;
use Illuminate\Pagination\LengthAwarePaginator;

class CandidateRepository implements CandidateRepositoryInterface
{
    public function find(int $id): ?Candidate
    {
        return Candidate::find($id);
    }

    public function create(array $data): Candidate
    {
        return Candidate::create($data);
    }

    public function update(Candidate $candidate, array $data): Candidate
    {
        $candidate->update($data);
        return $candidate->fresh();
    }

    public function delete(Candidate $candidate): bool
    {
        return $candidate->delete();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Candidate::query();

        // Apply filters
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('first_name', 'like', "%{$filters['search']}%")
                  ->orWhere('last_name', 'like', "%{$filters['search']}%")
                  ->orWhere('email', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->with(['profile', 'skills'])
                    ->orderBy('created_at', 'desc')
                    ->paginate($perPage);
    }

    public function search(array $criteria): array
    {
        $query = Candidate::query();

        // Basic search implementation
        if (isset($criteria['skills'])) {
            $query->whereHas('skills', function ($q) use ($criteria) {
                $q->whereIn('skill_id', $criteria['skills']);
            });
        }

        if (isset($criteria['experience_level'])) {
            $query->where('experience_level', $criteria['experience_level']);
        }

        return $query->get()->toArray();
    }

    public function advancedSearch(array $criteria): array
    {
        // TODO: Implement advanced search with AI/semantic search
        return $this->search($criteria);
    }
}
