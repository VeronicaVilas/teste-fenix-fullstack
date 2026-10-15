<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/teachers/{teacher}/exams',
    operationId: 'listTeacherExams',
    summary: 'Lista as provas do professor',
    description: 'Paginado, mais recentes primeiro, com contagem de questões e tentativas.',
    tags: ['Exams (Professor)'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published'])),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 10, maximum: 50, minimum: 1)),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Provas listadas',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Provas listadas com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/PaginatedExamSummary'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
#[OA\Post(
    path: '/teachers/{teacher}/exams',
    operationId: 'createExam',
    summary: 'Cria uma prova com questões e alternativas',
    description: 'A prova nasce como rascunho (draft). A posição de questões e alternativas segue a ordem dos arrays.',
    tags: ['Exams (Professor)'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreExamRequest')
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'Prova criada',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Prova criada com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 201),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ExamTeacher'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
#[OA\Get(
    path: '/teachers/{teacher}/exams/{exam}',
    operationId: 'showTeacherExam',
    summary: 'Exibe uma prova do professor com o gabarito',
    description: 'Questões e alternativas ordenadas por posição, com is_correct. Prova de outro professor retorna 404.',
    tags: ['Exams (Professor)'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
                    new OA\Property(property: 'data', ref: '#/components/schemas/ExamTeacher'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
    ]
)]
#[OA\Put(
    path: '/teachers/{teacher}/exams/{exam}',
    operationId: 'updateExam',
    summary: 'Atualiza uma prova em rascunho',
    description: 'Substitui título, descrição e todas as questões. Só rascunhos podem ser editados.',
    tags: ['Exams (Professor)'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'exam', description: 'Id da prova', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreExamRequest')
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Prova atualizada',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Prova atualizada com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', ref: '#/components/schemas/ExamTeacher'),
                ]
            )
        ),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(
            response: 409,
            description: 'Prova publicada não pode ser alterada',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: ['message' => 'Prova publicada não pode ser alterada.', 'status' => 409, 'errors' => [], 'data' => []]
            )
        ),
        new OA\Response(response: 422, ref: '#/components/responses/UnprocessableEntity'),
    ]
)]
#[OA\Delete(
    path: '/teachers/{teacher}/exams/{exam}',
    operationId: 'deleteExam',
    summary: 'Exclui uma prova',
    description: 'Rascunhos e provas publicadas sem tentativas podem ser excluídos. Prova já respondida retorna 409 para preservar o histórico e o ranking.',
    tags: ['Exams (Professor)'],
    parameters: [
        new OA\Parameter(name: 'teacher', description: 'Id do professor', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'exam', description: 'Id da prova', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 204, description: 'Prova excluída'),
        new OA\Response(response: 404, ref: '#/components/responses/NotFound'),
        new OA\Response(
            response: 409,
            description: 'Prova já respondida por alunos',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: ['message' => 'Prova já respondida por alunos não pode ser excluída.', 'status' => 409, 'errors' => [], 'data' => []]
            )
        ),
    ]
)]
final class ExamDocumentation {}
