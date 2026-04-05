<?php

namespace App\Services\AI;

use App\Contracts\AI\ResumeParserInterface;

class DefaultResumeParser implements ResumeParserInterface
{
    public function parse(string $filePath): array
    {
        // Stub: return fake parsed data
        return [
            'name' => 'John Doe',
            'skills' => ['PHP', 'Laravel'],
            'experience' => [],
            'education' => [],
            'contact' => ['email' => 'john@example.com'],
            'confidence_score' => 0.5,
        ];
    }

    public function extractSkills(string $content): array
    {
        return ['PHP', 'Laravel'];
    }

    public function extractExperience(string $content): array
    {
        return [];
    }

    public function extractEducation(string $content): array
    {
        return [];
    }

    public function extractContactInfo(string $content): array
    {
        return ['email' => 'john@example.com'];
    }
} 