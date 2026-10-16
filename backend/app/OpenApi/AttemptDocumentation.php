<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/students/{student}/exams/{exam}/attempt',
    operationId: 'submitAttempt',
    summary: 'Envia as respostas e corrige a prova automaticamente',
    description: 'Cada aluno responde cada prova uma única vez. Todas as questões devem ser respondidas. 1 ponto por acerto; percentual com 2 casas.',
    tags: ['Attempts'],
    parameters: [
        new OA\Parameter(name: 'student', description: 'Id do aluno', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'exam', description: 'Id da prova', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/SubmitAttemptRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Prova corrigida',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Prova enviada e corrigida com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 201),
                    new OA\Property(property: 'data', ref: '#/components/schemas/Attempt'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(
            response: 409,
            description: 'Prova já respondida pelo aluno',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: ['message' => 'Você já respondeu esta prova.', 'status' => 409, 'errors' => [], 'data' => []]
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Respostas inválidas (formato, questão faltando ou alternativa de outra questão)',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: ['message' => 'Responda todas as questões da prova.', 'status' => 422, 'errors' => [], 'data' => []]
            )
        ),
    ]
)]
final class AttemptDocumentation {}
