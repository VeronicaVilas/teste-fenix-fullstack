<?php

namespace App\Exceptions;

final class AttemptAlreadySubmittedException extends BusinessException
{
    public function __construct()
    {
        parent::__construct('Você já respondeu esta prova.');
    }
}
