<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Candidate\CandidateController;
use App\Http\Controllers\Candidate\ResumeController;
use App\Http\Controllers\Candidate\CandidateProfileController;
use App\Http\Controllers\Candidate\CandidateMatchingController;

Route::prefix('candidates')->group(function () {
    Route::get('/', [CandidateController::class, 'index']);
    Route::post('/', [CandidateController::class, 'store']);
    Route::get('/{id}', [CandidateController::class, 'show']);
    Route::put('/{id}', [CandidateController::class, 'update']);
    Route::delete('/{id}', [CandidateController::class, 'destroy']);
    
    // Profile routes
    Route::get('/{id}/profile', [CandidateProfileController::class, 'getProfile']);
    Route::put('/{id}/profile', [CandidateProfileController::class, 'updateProfile']);
    
    // Resume routes
    Route::post('/resume/upload', [ResumeController::class, 'upload']);
    Route::post('/resume/{id}/reparse', [ResumeController::class, 'reparse']);
    
    // Matching routes
    Route::get('/{id}/matches', [CandidateMatchingController::class, 'matchToJobs']);
    Route::post('/search', [CandidateMatchingController::class, 'searchCandidates']);
});
