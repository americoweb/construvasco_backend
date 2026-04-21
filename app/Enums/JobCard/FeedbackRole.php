<?php

namespace App\Enums\JobCard;

enum FeedbackRole: string
{
    case COMERCIAL = 'comercial';
    case DESIGNER  = 'designer';
    case CLIENT    = 'client';

    public function label(): string
    {
        return match($this) {
            self::COMERCIAL => 'Comercial',
            self::DESIGNER  => 'Designer',
            self::CLIENT    => 'Cliente',
        };
    }
}
