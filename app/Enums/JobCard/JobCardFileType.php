<?php

namespace App\Enums\JobCard;

enum JobCardFileType: string
{
    case BRIEFING  = 'briefing';
    case REFERENCE = 'reference';
    case DESIGN    = 'design';
    case PREVIEW   = 'preview';
    case FINAL     = 'final';

    public function label(): string
    {
        return match($this) {
            self::BRIEFING  => 'Briefing',
            self::REFERENCE => 'Referência',
            self::DESIGN    => 'Design',
            self::PREVIEW   => 'Preview',
            self::FINAL     => 'Arte Final',
        };
    }
}
