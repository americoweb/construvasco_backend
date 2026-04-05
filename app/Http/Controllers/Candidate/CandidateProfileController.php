<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\Candidate\CandidateService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateProfileController extends Controller
{
    public function __construct(
        private CandidateService $candidateService
    ) {}

    public function getProfile(int $candidateId): JsonResponse
    {
        $profile = $this->candidateService->getFullProfile($candidateId);
        
        return response()->json([
            'data' => $profile
        ]);
    }

    public function updateProfile(Request $request, int $candidateId): JsonResponse
    {
        $profile = $this->candidateService->updateProfile($candidateId, $request->all());
        
        return response()->json([
            'data' => $profile,
            'message' => 'Profile updated successfully'
        ]);
    }
}
