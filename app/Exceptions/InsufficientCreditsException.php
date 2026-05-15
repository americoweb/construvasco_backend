<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientCreditsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Créditos insuficientes. Adquira mais para continuar.');
    }
}
