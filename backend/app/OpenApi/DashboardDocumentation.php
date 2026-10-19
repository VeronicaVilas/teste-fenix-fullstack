<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/teachers/{teacher}/dashboard',
    operationId: 'dashboardSummary',
    summary: 'Resumo de desempenho dos alunos',
    description: 'Média da turma, melhor e pior tentativa, Top 1 e totais. Calculado no banco e guardado no Redis por 10 minutos, invalidado a cada nova tentativa, publicação ou exclusão.',
    tags: ['Dashboard'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Dashboard carregado',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Dashboard carregado com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/DashboardSummary'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Get(
    path: '/teachers/{teacher}/dashboard/exam-averages',
    operationId: 'dashboardExamAverages',
    summary: 'Média de cada prova publicada, paginada',
    description: 'Todas as provas publicadas do professor, inclusive sem tentativas (média nula), da publicação mais recente para a mais antiga. Aceita busca pelo título. Cada página fica no Redis por 10 minutos.',
    tags: ['Dashboard'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'search', description: 'Busca no título, sem diferenciar maiúsculas', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 255)),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50, minimum: 1)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Médias das provas carregadas',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Médias das provas carregadas com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedExamAverages'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
#[OA\Get(
    path: '/teachers/{teacher}/dashboard/exams-ranking',
    operationId: 'dashboardExamsRanking',
    summary: 'Ranking das provas pela média, paginado',
    description: 'Provas publicadas do professor com tentativas, ordenadas pela média (maior primeiro), com o número de alunos e o melhor aluno de cada prova. Posição calculada no banco com ROW_NUMBER() e cada página em cache no Redis.',
    tags: ['Dashboard'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50, minimum: 1)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Ranking das provas carregado',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Ranking das provas carregado com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedExamRanking'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
final class DashboardDocumentation {}
