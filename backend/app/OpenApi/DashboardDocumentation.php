<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/teachers/{teacher}/dashboard',
    operationId: 'dashboardSummary',
    summary: 'Resumo de desempenho dos alunos',
    description: 'Média, melhor (Top 1) e pior pontuação, totais e média por prova. Calculado no banco e guardado no Redis por 10 minutos, invalidado a cada nova tentativa, publicação ou exclusão.',
    tags: ['Dashboard'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'exam_id', description: 'Filtra as métricas por uma prova do professor', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
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
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
final class DashboardDocumentation {}
