<?php

namespace App\Services\Candidate;

use App\Repositories\Candidate\Contracts\CandidateRepositoryInterface;
use App\Models\Candidate\Candidate;
use App\Events\Candidate\CandidateCreated;
use App\Services\Shared\FormEngineService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CandidateService
{
    public function __construct(
        private CandidateRepositoryInterface $candidateRepository,
        private FormEngineService $formEngineService
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->candidateRepository->paginate($filters);
    }

    public function create(array $data): Candidate
    {
        // Process form data through form engine
        $processedData = $this->formEngineService->processFormData('candidate_registration', $data);
        
        $candidate = $this->candidateRepository->create($processedData);
        
        // Fire event for background processing
        event(new CandidateCreated($candidate));
        
        return $candidate;
    }

    public function findById(int $id): Candidate
    {
        $candidate = $this->candidateRepository->find($id);
        
        if (!$candidate) {
            throw new ModelNotFoundException('Candidate not found');
        }
        
        return $candidate;
    }

    public function update(int $id, array $data): Candidate
    {
        $candidate = $this->findById($id);
        
        // Process form data through form engine
        $processedData = $this->formEngineService->processFormData('candidate_update', $data);
        
        return $this->candidateRepository->update($candidate, $processedData);
    }

    public function delete(int $id): bool
    {
        $candidate = $this->findById($id);
        
        return $this->candidateRepository->delete($candidate);
    }

    public function getFullProfile(int $candidateId): array
    {
        $candidate = $this->findById($candidateId);
        
        return [
            'candidate' => $candidate->load([
                'profile',
                'skills',
                'experiences',
                'educations',
                'resumes'
            ])
        ];
    }

    public function updateProfile(int $candidateId, array $data): array
    {
        $candidate = $this->findById($candidateId);
        
        // Update related profile data
        if (isset($data['skills'])) {
            $candidate->skills()->sync($data['skills']);
        }
        
        if (isset($data['experiences'])) {
            $candidate->experiences()->delete();
            $candidate->experiences()->createMany($data['experiences']);
        }
        
        if (isset($data['educations'])) {
            $candidate->educations()->delete();
            $candidate->educations()->createMany($data['educations']);
        }
        
        return $this->getFullProfile($candidateId);
    }
}
