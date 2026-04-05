<?php

namespace App\Services\Candidate;

use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;

class CandidateSearchService
{
    public function __construct(
        private CandidateRepositoryInterface $candidateRepository
    ) {}

    public function search(array $criteria): array
    {
        return $this->candidateRepository->search($criteria);
    }

    public function advancedSearch(array $criteria): array
    {
        return $this->candidateRepository->advancedSearch($criteria);
    }
}
