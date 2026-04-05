<?php

namespace App\Services\AI\Contracts;

interface AIServiceInterface
{
    public function generateText(string $prompt, array $options = []): string;
    
    public function generateImage(string $prompt, array $options = []): string;
    
    public function generateStructuredResponse(string $prompt, array $schema, array $options = []): array;
}
