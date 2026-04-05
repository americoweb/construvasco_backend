<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\Candidate\CandidateMatchingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateMatchingController extends Controller
{
    public function __construct(
        private CandidateMatchingService $matchingService
    ) {}

    public function matchToJobs(int $candidateId): JsonResponse
    {
        $matches = $this->matchingService->findJobMatches($candidateId);
        
        return response()->json([
            'data' => $matches
        ]);
    }

    public function searchCandidates(Request $request): JsonResponse
    {
        $candidates = $this->matchingService->searchCandidates($request->all());
        
        return response()->json([
            'data' => $candidates
        ]);
    }
}
