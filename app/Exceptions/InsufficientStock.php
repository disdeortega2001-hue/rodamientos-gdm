<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientStock extends RuntimeException
{
    public static function for(string $code, int $available): self
    {
        return new self("No hay existencias suficientes de {$code}. Disponibles: {$available}.");
    }
}
