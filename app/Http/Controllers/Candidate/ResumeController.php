<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\Candidate\ResumeParserService;
use App\Http\Requests\Candidate\UploadResumeRequest;
use Illuminate\Http\JsonResponse;

class ResumeController extends Controller
{
    public function __construct(
        private ResumeParserService $resumeParserService
    ) {}

    public function upload(UploadResumeRequest $request): JsonResponse
    {
        $result = $this->resumeParserService->parseResume(
            $request->file('resume'),
            $request->input('candidate_id')
        );
        
        return response()->json([
            'data' => $result,
            'message' => 'Resume uploaded and parsed successfully'
        ]);
    }

    public function reparse(int $resumeId): JsonResponse
    {
        $result = $this->resumeParserService->reparseResume($resumeId);
        
        return response()->json([
            'data' => $result,
            'message' => 'Resume reparsed successfully'
        ]);
    }
}
