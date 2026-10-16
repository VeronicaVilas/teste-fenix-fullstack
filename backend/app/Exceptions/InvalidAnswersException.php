<?php

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class InvalidAnswersException extends BusinessException
{
    protected int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
}
