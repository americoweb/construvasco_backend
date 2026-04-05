<?php

namespace App\Enums\Design;

enum DesignStatus: string
{
    case DRAFT = 'draft';
    case GENERATING = 'generating';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFINED = 'refined';

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Rascunho',
            self::GENERATING => 'Gerando',
            self::COMPLETED => 'Concluído',
            self::FAILED => 'Falhou',
            self::REFINED => 'Refinado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::DRAFT => 'gray',
            self::GENERATING => 'blue',
            self::COMPLETED => 'green',
            self::FAILED => 'red',
            self::REFINED => 'purple',
        };
    }
}
