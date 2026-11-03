<?php

namespace App\Exceptions;

use RuntimeException;

class MissingPrice extends RuntimeException
{
    public static function for(string $code): self
    {
        return new self("Asigna un precio a {$code} antes de confirmar el pedido.");
    }
}
