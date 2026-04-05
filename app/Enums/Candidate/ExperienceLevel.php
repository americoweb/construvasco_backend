<?php

namespace App\Enums\Candidate;

enum ExperienceLevel: string
{
    case ENTRY = 'entry';
    case JUNIOR = 'junior';
    case MID = 'mid';
    case SENIOR = 'senior';
    case LEAD = 'lead';
    case PRINCIPAL = 'principal';
    case EXECUTIVE = 'executive';

    public function label(): string
    {
        return match($this) {
            self::ENTRY => 'Entry Level (0-1 years)',
            self::JUNIOR => 'Junior (1-3 years)',
            self::MID => 'Mid Level (3-5 years)',
            self::SENIOR => 'Senior (5-8 years)',
            self::LEAD => 'Lead (8-12 years)',
            self::PRINCIPAL => 'Principal (12+ years)',
            self::EXECUTIVE => 'Executive Level',
        };
    }
}
