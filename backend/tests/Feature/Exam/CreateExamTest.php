<?php

namespace Tests\Feature\Exam;

use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateExamTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/exams';

    private array $payload = [
        'title' => 'Prova de Geografia',
        'description' => 'Capitais e relevo',
        'questions' => [
            [
                'statement' => 'Qual é a capital do Brasil?',
                'options' => [
                    ['text' => 'São Paulo', 'is_correct' => false],
                    ['text' => 'Brasília', 'is_correct' => true],
                    ['text' => 'Rio de Janeiro', 'is_correct' => false],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_creates_draft_exam_and_returns_it_with_answer_key(): void
    {
        $this->mockTeacher();

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $exam = Exam::factory()->make(['id' => 10, 'teacher_id' => 1]);

            $mock->shouldReceive('create')->once()->with([
                'teacher_id' => 1,
                'title' => 'Prova de Geografia',
                'description' => 'Capitais e relevo',
            ])->andReturn($exam);
            $mock->shouldReceive('createQuestions')->once()->with($exam, $this->payload['questions']);
            $mock->shouldReceive('loadDetails')->once()->with($exam)->andReturn($this->detailedExam());
        });

        $this->postJson(self::URL, $this->payload)
            ->assertCreated()
            ->assertJsonPath('message', 'Prova criada com sucesso.')
            ->assertJsonPath('status', 201)
            ->assertJsonPath('data.id', 10)
            ->assertJsonPath('data.title', 'Prova de Geografia')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.published_at', null)
            ->assertJsonPath('data.questions_count', 1)
            ->assertJsonPath('data.questions.0.position', 1)
            ->assertJsonPath('data.questions.0.statement', 'Qual é a capital do Brasil?')
            ->assertJsonPath('data.questions.0.options.1', [
                'id' => 32,
                'position' => 2,
                'text' => 'Brasília',
                'is_correct' => true,
            ]);
    }

    public function test_returns_404_when_teacher_does_not_exist(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)->andThrow(new ModelNotFoundException);
        });
        $this->mockExamRepositoryNotCalled();

        $this->postJson(self::URL, $this->payload)
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    public function test_requires_title_and_questions(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $this->postJson(self::URL, [])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Os dados enviados são inválidos.')
            ->assertJsonPath('errors.title.0', 'O título é obrigatório.')
            ->assertJsonPath('errors.questions.0', 'A prova deve ter pelo menos uma questão.');
    }

    public function test_requires_at_least_two_options(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $payload = $this->payload;
        $payload['questions'][0]['options'] = [['text' => 'Brasília', 'is_correct' => true]];

        $this->postJson(self::URL, $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'questions.0.options' => ['A questão deve ter pelo menos 2 alternativas.'],
            ]);
    }

    public function test_rejects_options_that_are_not_a_list(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $payload = $this->payload;
        $payload['questions'][0]['options'] = 'Brasília';

        $this->postJson(self::URL, $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'questions.0.options' => ['As alternativas devem ser enviadas em uma lista.'],
            ]);
    }

    public function test_accepts_at_most_five_options(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $payload = $this->payload;
        $payload['questions'][0]['options'] = array_merge(
            [['text' => 'Brasília', 'is_correct' => true]],
            array_map(fn (int $number) => ['text' => "Opção {$number}", 'is_correct' => false], range(1, 5)),
        );

        $this->postJson(self::URL, $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'questions.0.options' => ['A questão deve ter no máximo 5 alternativas.'],
            ]);
    }

    public function test_requires_exactly_one_correct_option(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $noCorrect = $this->payload;
        $noCorrect['questions'][0]['options'][1]['is_correct'] = false;

        $twoCorrect = $this->payload;
        $twoCorrect['questions'][0]['options'][0]['is_correct'] = true;

        foreach ([$noCorrect, $twoCorrect] as $payload) {
            $this->postJson(self::URL, $payload)
                ->assertUnprocessable()
                ->assertJsonPath('errors', [
                    'questions.0.options' => ['Cada questão deve ter exatamente uma alternativa correta.'],
                ]);
        }
    }

    public function test_validates_question_and_option_fields(): void
    {
        $this->mockTeacher();
        $this->mockExamRepositoryNotCalled();

        $payload = $this->payload;
        $payload['questions'][0]['statement'] = '';
        $payload['questions'][0]['options'][0]['text'] = '';
        $payload['questions'][0]['options'][2]['is_correct'] = 'talvez';

        $errors = $this->postJson(self::URL, $payload)
            ->assertUnprocessable()
            ->json('errors');

        $this->assertSame(['O enunciado da questão é obrigatório.'], $errors['questions.0.statement']);
        $this->assertSame(['O texto da alternativa é obrigatório.'], $errors['questions.0.options.0.text']);
        $this->assertSame(
            ['O campo de alternativa correta deve ser verdadeiro ou falso.'],
            $errors['questions.0.options.2.is_correct'],
        );
    }

    private function mockTeacher(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    private function mockExamRepositoryNotCalled(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('create');
        });
    }

    private function detailedExam(): Exam
    {
        $question = Question::factory()->make(['id' => 20, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é a capital do Brasil?']);
        $question->setRelation('options', new Collection([
            Option::factory()->make(['id' => 31, 'question_id' => 20, 'position' => 1, 'text' => 'São Paulo']),
            Option::factory()->correct()->make(['id' => 32, 'question_id' => 20, 'position' => 2, 'text' => 'Brasília']),
            Option::factory()->make(['id' => 33, 'question_id' => 20, 'position' => 3, 'text' => 'Rio de Janeiro']),
        ]));

        $exam = Exam::factory()->make(['id' => 10, 'teacher_id' => 1, 'title' => 'Prova de Geografia', 'description' => 'Capitais e relevo']);
        $exam->setRelation('questions', new Collection([$question]));
        $exam->setAttribute('questions_count', 1);

        return $exam;
    }
}
