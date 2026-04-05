<?php

namespace App\Contracts\AI;

use App\Models\Candidate\Candidate;

interface MatchingEngineInterface
{
    public function findJobMatches(Candidate $candidate): array;
    
    public function searchCandidates(array $criteria): array;
    
    public function calculateMatchScore(Candidate $candidate, array $jobRequirements): float;
}
