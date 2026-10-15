<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'User',
    required: ['id', 'name', 'email'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Aluno Fênix'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'student@fenix.local'),
    ]
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    required: ['message', 'status', 'errors', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Recurso não encontrado.'),
        new OA\Property(property: 'status', type: 'integer', example: 404),
        new OA\Property(property: 'errors', type: 'object', example: []),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items, example: []),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    required: ['message', 'status', 'errors', 'data'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Os dados enviados são inválidos.'),
        new OA\Property(property: 'status', type: 'integer', example: 422),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(type: 'array', items: new OA\Items(type: 'string')),
            example: ['title' => ['O título é obrigatório.']]
        ),
        new OA\Property(property: 'data', type: 'array', items: new OA\Items, example: []),
    ]
)]
#[OA\Response(
    response: 'NotFound',
    description: 'Recurso não encontrado',
    content: new OA\JsonContent(ref: '#/components/schemas/ErrorResponse')
)]
#[OA\Response(
    response: 'UnprocessableEntity',
    description: 'Dados inválidos',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
)]
final class Schemas {}
