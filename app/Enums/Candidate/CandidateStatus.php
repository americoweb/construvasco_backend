<?php

namespace App\Enums\Candidate;

enum CandidateStatus: string
{
    case ACTIVE = 'active';
    case PASSIVE = 'passive';
    case PLACED = 'placed';
    case UNAVAILABLE = 'unavailable';
    case BLACKLISTED = 'blacklisted';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::PASSIVE => 'Passive',
            self::PLACED => 'Placed',
            self::UNAVAILABLE => 'Unavailable',
            self::BLACKLISTED => 'Blacklisted',
        };
    }
}
