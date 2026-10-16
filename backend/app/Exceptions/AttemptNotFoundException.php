<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class AttemptNotFoundException extends BusinessException
{
    protected int $statusCode = Response::HTTP_NOT_FOUND;

    public function __construct()
    {
        parent::__construct('Você ainda não respondeu esta prova.');
    }
}
