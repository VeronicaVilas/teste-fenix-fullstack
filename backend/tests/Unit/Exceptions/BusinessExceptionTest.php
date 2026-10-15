<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\BusinessException;
use Tests\TestCase;

class BusinessExceptionTest extends TestCase
{
    public function test_render_uses_default_conflict_status(): void
    {
        $exception = new class('Prova publicada não pode ser alterada.') extends BusinessException {};

        $response = $exception->render();

        $this->assertSame(409, $exception->getStatusCode());
        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Prova publicada não pode ser alterada.',
            'status' => 409,
            'errors' => [],
            'data' => [],
        ], $response->getData(true));
    }

    public function test_render_uses_subclass_status_code(): void
    {
        $exception = new class('Responda todas as questões da prova.') extends BusinessException
        {
            protected int $statusCode = 422;
        };

        $response = $exception->render();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(422, $response->getData(true)['status']);
        $this->assertSame('Responda todas as questões da prova.', $response->getData(true)['message']);
    }
}
