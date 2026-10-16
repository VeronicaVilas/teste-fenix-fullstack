<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/students/{student}/exams',
    operationId: 'listAvailableExams',
    summary: 'Lista as provas publicadas disponíveis para o aluno',
    description: 'Mais recentes primeiro. Cada prova informa se este aluno já respondeu e, se sim, a nota.',
    tags: ['Exams (Aluno)'],
    parameters: [
        new OA\Parameter(name: 'student', description: 'Id do aluno', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50, minimum: 1)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Provas disponíveis listadas',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Provas disponíveis listadas com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedAvailableExam'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
final class StudentExamDocumentation {}
