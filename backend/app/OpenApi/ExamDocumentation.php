<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

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
final class ExamDocumentation {}
