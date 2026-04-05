<?php

namespace App\Contracts\AI;

interface SkillExtractorInterface
{
    public function extractFromText(string $text): array;
    
    public function categorizeSkills(array $skills): array;
    
    public function validateSkill(string $skill): bool;
}
