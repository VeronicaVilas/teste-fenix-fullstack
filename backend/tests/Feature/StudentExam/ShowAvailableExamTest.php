<?php

namespace Tests\Feature\StudentExam;

use App\Enums\UserRole;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery\MockInterface;
use Tests\TestCase;

class ShowAvailableExamTest extends TestCase
{
    private const URL = '/api/v1/students/3/exams/10';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Student)
                ->andReturn(User::factory()->student()->make(['id' => 3]));
        });
    }

    public function test_shows_published_exam_without_answer_key(): void
    {
        $this->mockExam($this->publishedExam());
        $this->mockAnswered(false);

        $response = $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Prova encontrada.',
                'status' => 200,
                'data' => [
                    'id' => 10,
                    'title' => 'Prova de Geografia',
                    'description' => null,
                    'questions_count' => 1,
                    'answered' => false,
                    'questions' => [
                        [
                            'id' => 20,
                            'position' => 1,
                            'statement' => 'Qual é a capital do Brasil?',
                            'options' => [
                                ['id' => 31, 'position' => 1, 'text' => 'São Paulo'],
                                ['id' => 32, 'position' => 2, 'text' => 'Brasília'],
                            ],
                        ],
                    ],
                ],
            ]);

        $this->assertStringNotContainsString('is_correct', $response->getContent());
    }

    public function test_flags_exam_already_answered(): void
    {
        $this->mockExam($this->publishedExam());
        $this->mockAnswered(true);

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.answered', true);
    }

    public function test_returns_404_for_draft_or_missing_exam(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findPublishedWithQuestions')->once()->with(10)->andThrow(new ModelNotFoundException);
        });
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('existsFor');
        });

        $this->getJson(self::URL)
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    public function test_returns_404_when_user_is_not_a_student(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->andThrow(new ModelNotFoundException);
        });

        $this->getJson('/api/v1/students/1/exams/10')->assertNotFound();
    }

    private function mockExam(Exam $exam): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findPublishedWithQuestions')->once()->with(10)->andReturn($exam);
        });
    }

    private function mockAnswered(bool $answered): void
    {
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) use ($answered) {
            $mock->shouldReceive('existsFor')->once()->with(10, 3)->andReturn($answered);
        });
    }

    private function publishedExam(): Exam
    {
        $question = Question::factory()->make(['id' => 20, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é a capital do Brasil?']);
        $question->setRelation('options', new Collection([
            Option::factory()->make(['id' => 31, 'question_id' => 20, 'position' => 1, 'text' => 'São Paulo']),
            Option::factory()->correct()->make(['id' => 32, 'question_id' => 20, 'position' => 2, 'text' => 'Brasília']),
        ]));

        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1, 'title' => 'Prova de Geografia', 'description' => null]);
        $exam->setRelation('questions', new Collection([$question]));
        $exam->setAttribute('questions_count', 1);

        return $exam;
    }
}
