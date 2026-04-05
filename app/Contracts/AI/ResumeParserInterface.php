<?php

namespace App\Contracts\AI;

interface ResumeParserInterface
{
    public function parse(string $filePath): array;
    
    public function extractSkills(string $content): array;
    
    public function extractExperience(string $content): array;
    
    public function extractEducation(string $content): array;
    
    public function extractContactInfo(string $content): array;
}
