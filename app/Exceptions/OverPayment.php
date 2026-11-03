<?php

namespace App\Exceptions;

use RuntimeException;

class OverPayment extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El pago supera el saldo pendiente del pedido.');
    }
}
