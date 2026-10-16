<?php

namespace Tests\Unit\Services\Attempt;

use App\Exceptions\AttemptAlreadySubmittedException;
use App\Exceptions\InvalidAnswersException;
use App\Http\Services\Attempt\SubmitAttemptService;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SubmitAttemptServiceTest extends TestCase
{
    private User $student;

    private Exam $exam;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->make(['id' => 3]);
        $this->exam = $this->examWithThreeQuestions();

        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
        Cache::shouldReceive('tags')->with(['dashboard'])->andReturnSelf()->byDefault();
        Cache::shouldReceive('flush')->byDefault();
    }

    public function test_grades_answers_and_stores_the_attempt(): void
    {
        $answers = [
            ['question_id' => 1, 'option_id' => 11],
            ['question_id' => 2, 'option_id' => 22],
            ['question_id' => 3, 'option_id' => 32],
        ];

        $attempt = $this->submit($answers, function (MockInterface $mock) {
            $mock->shouldReceive('create')->once()
                ->withArgs(fn (array $data) => $data['exam_id'] === 10
                    && $data['student_id'] === 3
                    && $data['score'] === 2
                    && $data['total_questions'] === 3
                    && $data['percentage'] === 66.67
                    && $data['submitted_at'] !== null)
                ->andReturnUsing(fn (array $data) => new Attempt($data));
            $mock->shouldReceive('createAnswers')->once()
                ->withArgs(fn (Attempt $attempt, array $graded) => $graded === [
                    ['question_id' => 1, 'option_id' => 11, 'is_correct' => true],
                    ['question_id' => 2, 'option_id' => 22, 'is_correct' => false],
                    ['question_id' => 3, 'option_id' => 32, 'is_correct' => true],
                ]);
        });

        $this->assertSame(2, $attempt->score);
        $this->assertSame('66.67', $attempt->percentage);
    }

    public function test_full_and_zero_scores(): void
    {
        $all = [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21], ['question_id' => 3, 'option_id' => 32]];
        $none = [['question_id' => 1, 'option_id' => 12], ['question_id' => 2, 'option_id' => 22], ['question_id' => 3, 'option_id' => 31]];

        $full = $this->submit($all, $this->storesAttempt());
        $zero = $this->submit($none, $this->storesAttempt());

        $this->assertSame('100.00', $full->percentage);
        $this->assertSame('0.00', $zero->percentage);
    }

    public function test_answer_order_does_not_matter(): void
    {
        $answers = [['question_id' => 3, 'option_id' => 32], ['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21]];

        $this->assertSame(3, $this->submit($answers, $this->storesAttempt())->score);
    }

    public function test_clears_dashboard_cache(): void
    {
        Cache::shouldReceive('tags')->once()->with(['dashboard'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->submit($this->allCorrect(), $this->storesAttempt());
    }

    public function test_refuses_second_attempt(): void
    {
        $this->expectException(AttemptAlreadySubmittedException::class);
        $this->expectExceptionMessage('Você já respondeu esta prova.');

        $this->submit($this->allCorrect(), fn (MockInterface $mock) => $mock->shouldNotReceive('create'), alreadyAnswered: true);
    }

    public function test_converts_concurrent_duplicate_into_conflict(): void
    {
        $this->expectException(AttemptAlreadySubmittedException::class);

        $this->submit($this->allCorrect(), function (MockInterface $mock) {
            $mock->shouldReceive('create')->once()
                ->andThrow(new UniqueConstraintViolationException('pgsql', 'insert into attempts', [], new Exception));
        });
    }

    public function test_requires_every_question(): void
    {
        $this->expectException(InvalidAnswersException::class);
        $this->expectExceptionMessage('Responda todas as questões da prova.');

        $this->submit(
            [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21]],
            fn (MockInterface $mock) => $mock->shouldNotReceive('create'),
        );
    }

    public function test_rejects_questions_from_another_exam(): void
    {
        $this->expectException(InvalidAnswersException::class);
        $this->expectExceptionMessage('Responda todas as questões da prova.');

        $this->submit(
            [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21], ['question_id' => 99, 'option_id' => 991]],
            fn (MockInterface $mock) => $mock->shouldNotReceive('create'),
        );
    }

    public function test_rejects_option_of_another_question(): void
    {
        $this->expectException(InvalidAnswersException::class);
        $this->expectExceptionMessage('Alternativa inválida para a questão 2.');

        $this->submit(
            [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 11], ['question_id' => 3, 'option_id' => 32]],
            fn (MockInterface $mock) => $mock->shouldNotReceive('create'),
        );
    }

    private function submit(array $answers, callable $attemptExpectations, bool $alreadyAnswered = false): Attempt
    {
        $examRepository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findPublishedWithQuestions')->with(10)->andReturn($this->exam);
        });

        $attemptRepository = Mockery::mock(AttemptRepositoryInterface::class, function (MockInterface $mock) use ($attemptExpectations, $alreadyAnswered) {
            $mock->shouldReceive('existsFor')->with(10, 3)->andReturn($alreadyAnswered);
            $attemptExpectations($mock);
        });

        return (new SubmitAttemptService($examRepository, $attemptRepository))->handle($this->student, 10, $answers);
    }

    private function storesAttempt(): callable
    {
        return function (MockInterface $mock) {
            $mock->shouldReceive('create')->once()->andReturnUsing(fn (array $data) => new Attempt($data));
            $mock->shouldReceive('createAnswers')->once();
        };
    }

    private function allCorrect(): array
    {
        return [['question_id' => 1, 'option_id' => 11], ['question_id' => 2, 'option_id' => 21], ['question_id' => 3, 'option_id' => 32]];
    }

    private function examWithThreeQuestions(): Exam
    {
        $correctByQuestion = [1 => 11, 2 => 21, 3 => 32];

        $questions = collect([1, 2, 3])->map(function (int $questionId) use ($correctByQuestion) {
            $question = Question::factory()->make(['id' => $questionId, 'exam_id' => 10, 'position' => $questionId]);
            $question->setRelation('options', new Collection([
                Option::factory()->make(['id' => $questionId * 10 + 1, 'question_id' => $questionId, 'position' => 1, 'is_correct' => $correctByQuestion[$questionId] === $questionId * 10 + 1]),
                Option::factory()->make(['id' => $questionId * 10 + 2, 'question_id' => $questionId, 'position' => 2, 'is_correct' => $correctByQuestion[$questionId] === $questionId * 10 + 2]),
            ]));

            return $question;
        });

        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $exam->setRelation('questions', new Collection($questions->all()));

        return $exam;
    }
}
