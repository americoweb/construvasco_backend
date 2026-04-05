<?php

namespace App\Services\Candidate;

use App\Contracts\AI\ResumeParserInterface;
use App\Models\Candidate\Resume;
use App\Events\Candidate\ResumeUploaded;
use App\Services\Shared\FileStorageService;
use Illuminate\Http\UploadedFile;

class ResumeParserService
{
    public function __construct(
        private ResumeParserInterface $resumeParser,
        private FileStorageService $fileStorageService
    ) {}

    public function parseResume(UploadedFile $file, int $candidateId): array
    {
        // Store the file
        $filePath = $this->fileStorageService->store($file, 'resumes');
        
        // Create resume record
        $resume = Resume::create([
            'candidate_id' => $candidateId,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'status' => 'processing'
        ]);
        
        // Parse resume (AI or fallback)
        $parsedData = $this->resumeParser->parse($filePath);
        
        // Update resume with parsed data
        $resume->update([
            'parsed_data' => $parsedData,
            'status' => 'completed',
            'confidence_score' => $parsedData['confidence_score'] ?? null
        ]);
        
        // Fire event for further processing
        event(new ResumeUploaded($resume));
        
        return [
            'resume' => $resume,
            'parsed_data' => $parsedData
        ];
    }

    public function reparseResume(int $resumeId): array
    {
        $resume = Resume::findOrFail($resumeId);
        
        // Re-parse the stored file
        $parsedData = $this->resumeParser->parse($resume->file_path);
        
        // Update with new parsed data
        $resume->update([
            'parsed_data' => $parsedData,
            'confidence_score' => $parsedData['confidence_score'] ?? null,
            'reparsed_at' => now()
        ]);
        
        return [
            'resume' => $resume,
            'parsed_data' => $parsedData
        ];
    }
}
