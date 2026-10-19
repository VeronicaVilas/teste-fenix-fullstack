<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/students/{student}/dashboard',
    operationId: 'studentDashboard',
    summary: 'Resumo de desempenho do aluno',
    description: 'Provas respondidas e pendentes, média de acertos e melhor percentual. Calculado no banco com COUNT, AVG e MAX.',
    tags: ['Exams (Aluno)'],
    parameters: [
        new OA\Parameter(name: 'student', description: 'Id do aluno', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Dashboard carregado',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Dashboard carregado com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/StudentDashboard'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Get(
    path: '/students/{student}/exams',
    operationId: 'listAvailableExams',
    summary: 'Lista as provas publicadas disponíveis para o aluno',
    description: 'Mais recentes primeiro. Cada prova informa se este aluno já respondeu e, se sim, a nota. Aceita filtro por situação e busca pelo título.',
    tags: ['Exams (Aluno)'],
    parameters: [
        new OA\Parameter(name: 'student', description: 'Id do aluno', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', description: 'pending = ainda não respondidas; answered = já respondidas', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'answered'])),
        new OA\Parameter(name: 'search', description: 'Busca no título, sem diferenciar maiúsculas', in: 'query', required: false, schema: new OA\Schema(type: 'string', maxLength: 255)),
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
#[OA\Get(
    path: '/students/{student}/exams/{exam}',
    operationId: 'showAvailableExam',
    summary: 'Abre uma prova publicada para o aluno responder',
    description: 'Questões e alternativas sem o gabarito. Rascunho ou prova inexistente retornam 404. O conteúdo fica em cache, pois prova publicada é imutável.',
    tags: ['Exams (Aluno)'],
    parameters: [
        new OA\Parameter(name: 'student', description: 'Id do aluno', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'exam', description: 'Id da prova', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Prova encontrada',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Prova encontrada.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/StudentExam'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
final class StudentExamDocumentation {}
