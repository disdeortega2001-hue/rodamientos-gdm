<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidTransition extends RuntimeException
{
    public static function for(string $from, string $action): self
    {
        return new self("Un pedido en estado {$from} no se puede {$action}.");
    }
}
