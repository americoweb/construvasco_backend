<?php

namespace App\Services\AI;

use App\Contracts\AI\MatchingEngineInterface;
use App\Models\Candidate\Candidate;

class DefaultMatchingEngine implements MatchingEngineInterface
{
    public function findJobMatches(Candidate $candidate): array
    {
        // Stub: return empty matches
        return [];
    }

    public function searchCandidates(array $criteria): array
    {
        // Stub: return empty candidates
        return [];
    }

    public function calculateMatchScore(Candidate $candidate, array $jobRequirements): float
    {
        // Stub: return a default score
        return 0.0;
    }
} 