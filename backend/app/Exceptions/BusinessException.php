<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base for business rule violations. Subclasses define the message and,
 * when different from 409, the HTTP status code.
 */
abstract class BusinessException extends Exception
{
    protected int $statusCode = Response::HTTP_CONFLICT;

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'status' => $this->statusCode,
            'errors' => [],
            'data' => [],
        ], $this->statusCode);
    }
}
