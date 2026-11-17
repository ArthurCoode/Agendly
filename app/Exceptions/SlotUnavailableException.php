<?php

namespace App\Exceptions;

use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'Esse horário acabou de ser ocupado. Escolha outro, por favor.')
    {
        parent::__construct($message);
    }
}
