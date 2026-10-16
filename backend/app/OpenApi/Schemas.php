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
#[OA\Schema(
    schema: 'OptionTeacher',
    required: ['id', 'position', 'text', 'is_correct'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'position', type: 'integer', example: 2),
        new OA\Property(property: 'text', type: 'string', example: 'Brasília'),
        new OA\Property(property: 'is_correct', type: 'boolean', example: true),
    ]
)]
#[OA\Schema(
    schema: 'QuestionTeacher',
    required: ['id', 'position', 'statement', 'options'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'position', type: 'integer', example: 1),
        new OA\Property(property: 'statement', type: 'string', example: 'Qual é a capital do Brasil?'),
        new OA\Property(property: 'options', type: 'array', items: new OA\Items(ref: '#/components/schemas/OptionTeacher')),
    ]
)]
#[OA\Schema(
    schema: 'ExamTeacher',
    required: ['id', 'title', 'status', 'questions'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Geografia'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Capitais e relevo'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'questions_count', type: 'integer', example: 1),
        new OA\Property(property: 'attempts_count', type: 'integer', example: 0),
        new OA\Property(property: 'questions', type: 'array', items: new OA\Items(ref: '#/components/schemas/QuestionTeacher')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'StoreExamRequest',
    required: ['title', 'questions'],
    properties: [
        new OA\Property(property: 'title', type: 'string', maxLength: 255, example: 'Prova de Geografia'),
        new OA\Property(property: 'description', type: 'string', maxLength: 2000, nullable: true, example: 'Capitais e relevo'),
        new OA\Property(
            property: 'questions',
            type: 'array',
            minItems: 1,
            maxItems: 100,
            items: new OA\Items(
                required: ['statement', 'options'],
                properties: [
                    new OA\Property(property: 'statement', type: 'string', maxLength: 2000, example: 'Qual é a capital do Brasil?'),
                    new OA\Property(
                        property: 'options',
                        description: 'De 2 a 5 alternativas, exatamente uma com is_correct = true. A ordem do array define a posição.',
                        type: 'array',
                        minItems: 2,
                        maxItems: 5,
                        items: new OA\Items(
                            required: ['text', 'is_correct'],
                            properties: [
                                new OA\Property(property: 'text', type: 'string', maxLength: 500, example: 'Brasília'),
                                new OA\Property(property: 'is_correct', type: 'boolean', example: true),
                            ]
                        )
                    ),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    required: ['current_page', 'last_page', 'per_page', 'total'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 10),
        new OA\Property(property: 'total', type: 'integer', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'ExamSummary',
    required: ['id', 'title', 'status', 'questions_count', 'attempts_count'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Geografia'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published'], example: 'draft'),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true, example: null),
        new OA\Property(property: 'questions_count', type: 'integer', example: 5),
        new OA\Property(property: 'attempts_count', type: 'integer', example: 0),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedExamSummary',
    required: ['items', 'meta'],
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/ExamSummary')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'AttemptSummary',
    required: ['score', 'total_questions', 'percentage', 'submitted_at'],
    properties: [
        new OA\Property(property: 'score', type: 'integer', example: 4),
        new OA\Property(property: 'total_questions', type: 'integer', example: 5),
        new OA\Property(property: 'percentage', type: 'string', example: '80.00'),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AvailableExam',
    required: ['id', 'title', 'questions_count', 'published_at', 'answered', 'attempt'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Geografia'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Capitais e relevo'),
        new OA\Property(property: 'questions_count', type: 'integer', example: 5),
        new OA\Property(property: 'published_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'answered', type: 'boolean', example: true),
        new OA\Property(property: 'attempt', ref: '#/components/schemas/AttemptSummary', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'PaginatedAvailableExam',
    required: ['items', 'meta'],
    properties: [
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/AvailableExam')),
        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
    ]
)]
#[OA\Schema(
    schema: 'StudentExam',
    description: 'Prova vista pelo aluno: nunca contém is_correct.',
    required: ['id', 'title', 'questions_count', 'answered', 'questions'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'title', type: 'string', example: 'Prova de Geografia'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Capitais e relevo'),
        new OA\Property(property: 'questions_count', type: 'integer', example: 1),
        new OA\Property(property: 'answered', type: 'boolean', example: false),
        new OA\Property(
            property: 'questions',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'position', type: 'integer', example: 1),
                    new OA\Property(property: 'statement', type: 'string', example: 'Qual é a capital do Brasil?'),
                    new OA\Property(
                        property: 'options',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 32),
                                new OA\Property(property: 'position', type: 'integer', example: 2),
                                new OA\Property(property: 'text', type: 'string', example: 'Brasília'),
                            ]
                        )
                    ),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'SubmitAttemptRequest',
    required: ['answers'],
    properties: [
        new OA\Property(
            property: 'answers',
            description: 'Uma resposta para cada questão da prova.',
            type: 'array',
            items: new OA\Items(
                required: ['question_id', 'option_id'],
                properties: [
                    new OA\Property(property: 'question_id', type: 'integer', example: 10),
                    new OA\Property(property: 'option_id', type: 'integer', example: 32),
                ]
            )
        ),
    ]
)]
#[OA\Schema(
    schema: 'Attempt',
    required: ['id', 'exam_id', 'score', 'total_questions', 'percentage', 'submitted_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(property: 'exam_id', type: 'integer', example: 3),
        new OA\Property(property: 'score', type: 'integer', example: 4),
        new OA\Property(property: 'total_questions', type: 'integer', example: 5),
        new OA\Property(property: 'percentage', type: 'string', example: '80.00'),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'AttemptResult',
    description: 'Revisão da tentativa: nota, resposta dada e gabarito de cada questão.',
    required: ['id', 'exam', 'score', 'total_questions', 'percentage', 'submitted_at', 'questions'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 7),
        new OA\Property(
            property: 'exam',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'title', type: 'string', example: 'Prova de Geografia'),
            ],
            type: 'object'
        ),
        new OA\Property(property: 'score', type: 'integer', example: 4),
        new OA\Property(property: 'total_questions', type: 'integer', example: 5),
        new OA\Property(property: 'percentage', type: 'string', example: '80.00'),
        new OA\Property(property: 'submitted_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'questions',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 10),
                    new OA\Property(property: 'position', type: 'integer', example: 1),
                    new OA\Property(property: 'statement', type: 'string', example: 'Qual é a capital do Brasil?'),
                    new OA\Property(property: 'selected_option_id', type: 'integer', example: 31),
                    new OA\Property(property: 'correct_option_id', type: 'integer', example: 32),
                    new OA\Property(property: 'is_correct', type: 'boolean', example: false),
                    new OA\Property(property: 'options', type: 'array', items: new OA\Items(ref: '#/components/schemas/OptionTeacher')),
                ]
            )
        ),
    ]
)]
final class Schemas {}
