<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\Candidate\CandidateService;
use App\Http\Requests\Candidate\StoreCandidateRequest;
use App\Http\Requests\Candidate\UpdateCandidateRequest;
use App\Http\Resources\Candidate\CandidateResource;
use App\Http\Resources\Candidate\CandidateListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CandidateController extends Controller
{
    public function __construct(
        private CandidateService $candidateService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $candidates = $this->candidateService->list($request->all());
        
        return response()->json([
            'data' => CandidateListResource::collection($candidates->items()),
            'meta' => [
                'current_page' => $candidates->currentPage(),
                'last_page' => $candidates->lastPage(),
                'per_page' => $candidates->perPage(),
                'total' => $candidates->total(),
            ]
        ]);
    }

    public function store(StoreCandidateRequest $request): JsonResponse
    {
        $candidate = $this->candidateService->create($request->validated());
        
        return response()->json([
            'data' => new CandidateResource($candidate),
            'message' => 'Candidate created successfully'
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $candidate = $this->candidateService->findById($id);
        
        return response()->json([
            'data' => new CandidateResource($candidate)
        ]);
    }

    public function update(UpdateCandidateRequest $request, int $id): JsonResponse
    {
        $candidate = $this->candidateService->update($id, $request->validated());
        
        return response()->json([
            'data' => new CandidateResource($candidate),
            'message' => 'Candidate updated successfully'
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->candidateService->delete($id);
        
        return response()->json([
            'message' => 'Candidate deleted successfully'
        ]);
    }
}
