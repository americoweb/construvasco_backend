<?php

namespace App\Enums\JobCard;

enum ClientTier: string
{
    case VIP    = 'vip';
    case NORMAL = 'normal';
    case NEW    = 'new';

    public function label(): string
    {
        return match($this) {
            self::VIP    => 'VIP',
            self::NORMAL => 'Normal',
            self::NEW    => 'Novo',
        };
    }

    public function scoreBonus(): int
    {
        return match($this) {
            self::VIP    => 200,
            self::NORMAL => 0,
            self::NEW    => 0,
        };
    }
}
