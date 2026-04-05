<?php

namespace App\Services\Candidate;

use App\Contracts\AI\MatchingEngineInterface;
use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;

class CandidateMatchingService
{
    public function __construct(
        private MatchingEngineInterface $matchingEngine,
        private CandidateRepositoryInterface $candidateRepository
    ) {}

    public function findJobMatches(int $candidateId): array
    {
        $candidate = $this->candidateRepository->find($candidateId);
        
        if (!$candidate) {
            return [];
        }
        
        return $this->matchingEngine->findJobMatches($candidate);
    }

    public function searchCandidates(array $criteria): array
    {
        return $this->matchingEngine->searchCandidates($criteria);
    }
}
