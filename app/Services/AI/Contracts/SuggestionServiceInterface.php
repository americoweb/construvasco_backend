<?php

namespace App\Services\AI\Contracts;

use App\DTOs\AI\SuggestionRequest;

interface SuggestionServiceInterface
{
    public function getSmartSuggestions(SuggestionRequest $request): array;
    
    public function generateMockup(int $productId, array $designData): string;
    
    public function refineDesign(string $currentPrompt, string $feedback): string;
}
