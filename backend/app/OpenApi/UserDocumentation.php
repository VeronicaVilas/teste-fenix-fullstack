<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/teachers',
    operationId: 'listTeachers',
    summary: 'Lista os professores',
    description: 'Usado pelo acesso "Professor" do frontend. Ordenado por nome.',
    tags: ['Users'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Professores listados',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Professores listados com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                ]
            )
        ),
    ]
)]
#[OA\Get(
    path: '/students',
    operationId: 'listStudents',
    summary: 'Lista os alunos',
    description: 'Usado pelo acesso "Aluno" do frontend para escolher qual aluno fará a prova. Ordenado por nome.',
    tags: ['Users'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Alunos listados',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Alunos listados com sucesso.'),
                    new OA\Property(property: 'status', type: 'integer', example: 200),
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/User')),
                ]
            )
        ),
    ]
)]
final class UserDocumentation {}
