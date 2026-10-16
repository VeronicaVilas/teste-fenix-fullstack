<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\SubmitAttemptRequest;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class SubmitAttemptRequestTest extends TestCase
{
    use BuildsFormRequests;

    public function test_accepts_one_answer_per_question(): void
    {
        $answers = [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21]];

        $request = $this->validatedRequest(SubmitAttemptRequest::class, ['answers' => $answers]);

        $this->assertTrue($request->authorize());
        $this->assertSame($answers, $request->validated('answers'));
    }

    public function test_requires_answers(): void
    {
        $this->assertSame(
            ['answers' => ['Envie as respostas da prova.']],
            $this->validationErrors(SubmitAttemptRequest::class, ['answers' => []]),
        );
    }

    public function test_rejects_repeated_questions_and_invalid_ids(): void
    {
        $errors = $this->validationErrors(SubmitAttemptRequest::class, ['answers' => [
            ['question_id' => 1, 'option_id' => 11],
            ['question_id' => 1, 'option_id' => 'abc'],
            ['option_id' => 21],
        ]]);

        $this->assertSame(['Cada questão deve ser respondida apenas uma vez.'], $errors['answers.0.question_id']);
        $this->assertSame(['A alternativa deve ser um número inteiro.'], $errors['answers.1.option_id']);
        $this->assertSame(['Informe a questão respondida.'], $errors['answers.2.question_id']);
    }
}
