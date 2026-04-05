<?php

namespace App\Enums\Product;

enum PricingType: string
{
    case FIXED = 'fixed';
    case SQM_BASED = 'sqm_based';

    public function label(): string
    {
        return match($this) {
            self::FIXED => 'Preço Fixo',
            self::SQM_BASED => 'Por Metro Quadrado',
        };
    }
}

