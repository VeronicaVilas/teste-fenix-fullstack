<?php

namespace Tests\Feature\Attempt;

use App\Enums\UserRole;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class SubmitAttemptTest extends TestCase
{
    private const URL = '/api/v1/students/3/exams/10/attempt';

    private array $payload = [
        'answers' => [
            ['question_id' => 1, 'option_id' => 12],
            ['question_id' => 2, 'option_id' => 21],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Student)
                ->andReturn(User::factory()->student()->make(['id' => 3]));
        });
    }

    public function test_grades_and_returns_the_attempt(): void
    {
        Carbon::setTestNow('2026-10-16 10:00:00');
        Cache::tags(['dashboard'])->put('dashboard:teacher:1:summary:exam:all', 'cached summary');

        $this->mockExam();
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('existsFor')->once()->with(10, 3)->andReturn(false);
            $mock->shouldReceive('create')->once()
                ->andReturnUsing(fn (array $data) => (new Attempt($data))->forceFill(['id' => 7]));
            $mock->shouldReceive('createAnswers')->once()
                ->withArgs(fn (Attempt $attempt, array $answers) => count($answers) === 2);
        });

        $this->postJson(self::URL, $this->payload)
            ->assertCreated()
            ->assertExactJson([
                'message' => 'Prova enviada e corrigida com sucesso.',
                'status' => 201,
                'data' => [
                    'id' => 7,
                    'exam_id' => 10,
                    'score' => 1,
                    'total_questions' => 2,
                    'percentage' => '50.00',
                    'submitted_at' => '2026-10-16T10:00:00+00:00',
                ],
            ]);

        $this->assertNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary:exam:all'));
    }

    public function test_returns_409_when_already_answered(): void
    {
        $this->mockExam();
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('existsFor')->once()->andReturn(true);
            $mock->shouldNotReceive('create');
        });

        $this->postJson(self::URL, $this->payload)
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Você já respondeu esta prova.',
                'status' => 409,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_returns_409_on_concurrent_duplicate(): void
    {
        $this->mockExam();
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('existsFor')->once()->andReturn(false);
            $mock->shouldReceive('create')->once()
                ->andThrow(new UniqueConstraintViolationException('pgsql', 'insert into attempts', [], new Exception));
        });

        $this->postJson(self::URL, $this->payload)->assertConflict();
    }

    public function test_returns_404_for_draft_or_missing_exam(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findPublishedWithQuestions')->once()->andThrow(new ModelNotFoundException);
        });
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('create');
        });

        $this->postJson(self::URL, $this->payload)->assertNotFound();
    }

    public function test_validates_payload_format(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('findPublishedWithQuestions');
        });

        $this->postJson(self::URL, ['answers' => []])
            ->assertUnprocessable()
            ->assertJsonPath('errors.answers.0', 'Envie as respostas da prova.');

        $errors = $this->postJson(self::URL, ['answers' => [
            ['question_id' => 1, 'option_id' => 12],
            ['question_id' => 1, 'option_id' => 11],
        ]])->assertUnprocessable()->json('errors');

        $this->assertSame(['Cada questão deve ser respondida apenas uma vez.'], $errors['answers.0.question_id']);
    }

    public function test_returns_422_for_option_of_another_exam(): void
    {
        $this->mockExam();
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('existsFor')->once()->andReturn(false);
            $mock->shouldNotReceive('create');
        });

        $this->postJson(self::URL, ['answers' => [
            ['question_id' => 1, 'option_id' => 12],
            ['question_id' => 2, 'option_id' => 999],
        ]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Alternativa inválida para a questão 2.');
    }

    public function test_returns_422_when_a_question_is_missing(): void
    {
        $this->mockExam();
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('existsFor')->once()->andReturn(false);
            $mock->shouldNotReceive('create');
        });

        $this->postJson(self::URL, ['answers' => [['question_id' => 1, 'option_id' => 12]]])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Responda todas as questões da prova.');
    }

    private function mockExam(): void
    {
        $questions = collect([1, 2])->map(function (int $questionId) {
            $question = Question::factory()->make(['id' => $questionId, 'exam_id' => 10, 'position' => $questionId]);
            $question->setRelation('options', new Collection([
                Option::factory()->correct()->make(['id' => $questionId * 10 + 1, 'question_id' => $questionId, 'position' => 1]),
                Option::factory()->make(['id' => $questionId * 10 + 2, 'question_id' => $questionId, 'position' => 2]),
            ]));

            return $question;
        });

        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $exam->setRelation('questions', new Collection($questions->all()));

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findPublishedWithQuestions')->once()->with(10)->andReturn($exam);
        });
    }
}
