<?php

namespace Tests\Unit\Traits;

use App\Traits\HttpResponses;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\MessageBag;
use Tests\TestCase;

class HttpResponsesTest extends TestCase
{
    private object $responder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->responder = new class
        {
            use HttpResponses;
        };
    }

    public function test_response_returns_success_envelope_with_status(): void
    {
        $response = $this->responder->response('Prova criada com sucesso.', 201, ['id' => 1]);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Prova criada com sucesso.',
            'status' => 201,
            'data' => ['id' => 1],
        ], $response->getData(true));
    }

    public function test_response_accepts_json_resource_as_data(): void
    {
        $response = $this->responder->response('Ok.', 200, new JsonResource(['name' => 'Fênix']));

        $this->assertSame(['name' => 'Fênix'], $response->getData(true)['data']);
    }

    public function test_error_returns_error_envelope_with_errors(): void
    {
        $response = $this->responder->error('Os dados enviados são inválidos.', 422, ['title' => ['Obrigatório.']]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Os dados enviados são inválidos.',
            'status' => 422,
            'errors' => ['title' => ['Obrigatório.']],
            'data' => [],
        ], $response->getData(true));
    }

    public function test_error_accepts_message_bag(): void
    {
        $response = $this->responder->error('Erro.', 422, new MessageBag(['title' => 'Obrigatório.']));

        $this->assertSame(['title' => ['Obrigatório.']], $response->getData(true)['errors']);
    }

    public function test_paginated_returns_items_and_meta(): void
    {
        $paginator = new LengthAwarePaginator(
            items: [['id' => 3], ['id' => 4]],
            total: 4,
            perPage: 2,
            currentPage: 2,
        );

        $response = $this->responder->paginated('Provas listadas com sucesso.', $paginator, JsonResource::class);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Provas listadas com sucesso.',
            'status' => 200,
            'data' => [
                'items' => [['id' => 3], ['id' => 4]],
                'meta' => ['current_page' => 2, 'last_page' => 2, 'per_page' => 2, 'total' => 4],
            ],
        ], $response->getData(true));
    }
}
