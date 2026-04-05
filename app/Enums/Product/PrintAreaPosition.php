<?php

namespace App\Enums\Product;

enum PrintAreaPosition: string
{
    case FRONT_CHEST = 'front_chest';
    case BACK_FULL = 'back_full';
    case FULL_WRAP = 'full_wrap';
    case FRONT = 'front';
    case BACK = 'back';
    case LEFT_CHEST = 'left_chest';
    case FRONT_COVER = 'front_cover';
    case BODY = 'body';
    case FULL_POSTER = 'full_poster';

    public function label(): string
    {
        return match($this) {
            self::FRONT_CHEST => 'Frente (Peito)',
            self::BACK_FULL => 'Costas (Completo)',
            self::FULL_WRAP => 'Envolvente Completo',
            self::FRONT => 'Frente',
            self::BACK => 'Costas',
            self::LEFT_CHEST => 'Peito Esquerdo',
            self::FRONT_COVER => 'Capa Frontal',
            self::BODY => 'Corpo',
            self::FULL_POSTER => 'Poster Completo',
        };
    }
}
