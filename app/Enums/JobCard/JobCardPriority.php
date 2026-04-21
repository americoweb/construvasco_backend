<?php

namespace App\Enums\JobCard;

enum JobCardPriority: string
{
    case LOW    = 'low';
    case MEDIUM = 'medium';
    case HIGH   = 'high';

    public function label(): string
    {
        return match($this) {
            self::LOW    => 'Baixa',
            self::MEDIUM => 'Média',
            self::HIGH   => 'Alta',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::LOW    => 'green',
            self::MEDIUM => 'yellow',
            self::HIGH   => 'red',
        };
    }

    public function weight(): int
    {
        return match($this) {
            self::LOW    => 1,
            self::MEDIUM => 2,
            self::HIGH   => 3,
        };
    }
}
