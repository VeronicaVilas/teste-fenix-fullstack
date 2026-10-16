<?php

namespace Tests\Unit\Http\Requests;

use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class StoreExamRequestTest extends TestCase
{
    use BuildsFormRequests;

    private array $valid = [
        'title' => 'Prova de Geografia',
        'description' => 'Capitais',
        'questions' => [
            [
                'statement' => 'Qual é a capital do Brasil?',
                'options' => [
                    ['text' => 'São Paulo', 'is_correct' => false],
                    ['text' => 'Brasília', 'is_correct' => true],
                ],
            ],
        ],
    ];

    public function test_accepts_a_valid_exam(): void
    {
        $request = $this->validatedRequest(StoreExamRequest::class, $this->valid);

        $this->assertTrue($request->authorize());
        $this->assertSame($this->valid, $request->validated());
    }

    public function test_requires_title_and_questions(): void
    {
        $this->assertSame([
            'title' => ['O título é obrigatório.'],
            'questions' => ['A prova deve ter pelo menos uma questão.'],
        ], $this->validationErrors(StoreExamRequest::class, []));
    }

    public function test_limits_title_description_and_number_of_questions(): void
    {
        $data = $this->valid;
        $data['title'] = str_repeat('a', 256);
        $data['description'] = str_repeat('a', 2001);
        $data['questions'] = array_fill(0, 101, $this->valid['questions'][0]);

        $this->assertSame([
            'title' => ['O título deve ter no máximo 255 caracteres.'],
            'description' => ['A descrição deve ter no máximo 2000 caracteres.'],
            'questions' => ['A prova deve ter no máximo 100 questões.'],
        ], $this->validationErrors(StoreExamRequest::class, $data));
    }

    public function test_requires_between_two_and_five_options(): void
    {
        $tooFew = $this->valid;
        $tooFew['questions'][0]['options'] = [['text' => 'Brasília', 'is_correct' => true]];

        $tooMany = $this->valid;
        $tooMany['questions'][0]['options'] = array_merge(
            [['text' => 'Brasília', 'is_correct' => true]],
            array_fill(0, 5, ['text' => 'Outra', 'is_correct' => false]),
        );

        $this->assertSame(
            ['questions.0.options' => ['A questão deve ter pelo menos 2 alternativas.']],
            $this->validationErrors(StoreExamRequest::class, $tooFew),
        );
        $this->assertSame(
            ['questions.0.options' => ['A questão deve ter no máximo 5 alternativas.']],
            $this->validationErrors(StoreExamRequest::class, $tooMany),
        );
    }

    public function test_requires_exactly_one_correct_option(): void
    {
        $none = $this->valid;
        $none['questions'][0]['options'][1]['is_correct'] = false;

        $two = $this->valid;
        $two['questions'][0]['options'][0]['is_correct'] = '1';

        foreach ([$none, $two] as $data) {
            $this->assertSame(
                ['questions.0.options' => [StoreExamRequest::ONE_CORRECT_OPTION_MESSAGE]],
                $this->validationErrors(StoreExamRequest::class, $data),
            );
        }
    }

    public function test_stops_at_type_error_when_options_are_not_a_list(): void
    {
        $data = $this->valid;
        $data['questions'][0]['options'] = 'Brasília';

        $this->assertSame(
            ['questions.0.options' => ['As alternativas devem ser enviadas em uma lista.']],
            $this->validationErrors(StoreExamRequest::class, $data),
        );
    }

    public function test_validates_question_and_option_fields(): void
    {
        $data = $this->valid;
        $data['questions'][0]['statement'] = '';
        $data['questions'][0]['options'][0]['text'] = str_repeat('a', 501);
        $data['questions'][0]['options'][1]['is_correct'] = 'talvez';

        $errors = $this->validationErrors(StoreExamRequest::class, $data);

        $this->assertSame(['O enunciado da questão é obrigatório.'], $errors['questions.0.statement']);
        $this->assertSame(['O texto da alternativa deve ter no máximo 500 caracteres.'], $errors['questions.0.options.0.text']);
        $this->assertSame(
            ['O campo de alternativa correta deve ser verdadeiro ou falso.'],
            $errors['questions.0.options.1.is_correct'],
        );
    }

    public function test_update_request_uses_the_same_rules(): void
    {
        $this->assertSame(
            $this->validationErrors(StoreExamRequest::class, []),
            $this->validationErrors(UpdateExamRequest::class, [], 'PUT'),
        );
        $this->assertSame($this->valid, $this->validatedRequest(UpdateExamRequest::class, $this->valid, 'PUT')->validated());
    }
}
