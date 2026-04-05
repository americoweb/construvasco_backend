<?php

namespace App\Enums\Product;

enum ProductStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case OUT_OF_STOCK = 'out_of_stock';
    case DISCONTINUED = 'discontinued';

    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::INACTIVE => 'Inativo',
            self::OUT_OF_STOCK => 'Fora de Estoque',
            self::DISCONTINUED => 'Descontinuado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::OUT_OF_STOCK => 'yellow',
            self::DISCONTINUED => 'red',
        };
    }
}
