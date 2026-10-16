<?php

namespace Tests\Feature\Attempt;

use App\Enums\UserRole;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class ShowAttemptResultTest extends TestCase
{
    private const URL = '/api/v1/students/3/exams/10/attempt';

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Student)
                ->andReturn(User::factory()->student()->make(['id' => 3]));
        });
    }

    public function test_shows_score_and_review_per_question(): void
    {
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForStudentWithReview')->once()->with(10, 3)->andReturn($this->reviewedAttempt());
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Resultado encontrado.',
                'status' => 200,
                'data' => [
                    'id' => 7,
                    'exam' => ['id' => 10, 'title' => 'Prova de Geografia'],
                    'score' => 1,
                    'total_questions' => 2,
                    'percentage' => '50.00',
                    'submitted_at' => '2026-10-16T10:00:00+00:00',
                    'questions' => [
                        [
                            'id' => 1,
                            'position' => 1,
                            'statement' => 'Qual é a capital do Brasil?',
                            'selected_option_id' => 11,
                            'correct_option_id' => 12,
                            'is_correct' => false,
                            'options' => [
                                ['id' => 11, 'position' => 1, 'text' => 'São Paulo', 'is_correct' => false],
                                ['id' => 12, 'position' => 2, 'text' => 'Brasília', 'is_correct' => true],
                            ],
                        ],
                        [
                            'id' => 2,
                            'position' => 2,
                            'statement' => 'Qual é a capital da Argentina?',
                            'selected_option_id' => 21,
                            'correct_option_id' => 21,
                            'is_correct' => true,
                            'options' => [
                                ['id' => 21, 'position' => 1, 'text' => 'Buenos Aires', 'is_correct' => true],
                                ['id' => 22, 'position' => 2, 'text' => 'Lima', 'is_correct' => false],
                            ],
                        ],
                    ],
                ],
            ]);
    }

    public function test_returns_404_with_message_when_not_answered(): void
    {
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForStudentWithReview')->once()->with(10, 3)->andReturn(null);
        });

        $this->getJson(self::URL)
            ->assertNotFound()
            ->assertExactJson([
                'message' => 'Você ainda não respondeu esta prova.',
                'status' => 404,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_returns_404_when_user_is_not_a_student(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->andThrow(new ModelNotFoundException);
        });
        $this->mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('findForStudentWithReview');
        });

        $this->getJson('/api/v1/students/1/exams/10/attempt')->assertNotFound();
    }

    private function reviewedAttempt(): Attempt
    {
        $brazil = Question::factory()->make(['id' => 1, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é a capital do Brasil?']);
        $brazil->setRelation('options', new Collection([
            Option::factory()->make(['id' => 11, 'question_id' => 1, 'position' => 1, 'text' => 'São Paulo']),
            Option::factory()->correct()->make(['id' => 12, 'question_id' => 1, 'position' => 2, 'text' => 'Brasília']),
        ]));

        $argentina = Question::factory()->make(['id' => 2, 'exam_id' => 10, 'position' => 2, 'statement' => 'Qual é a capital da Argentina?']);
        $argentina->setRelation('options', new Collection([
            Option::factory()->correct()->make(['id' => 21, 'question_id' => 2, 'position' => 1, 'text' => 'Buenos Aires']),
            Option::factory()->make(['id' => 22, 'question_id' => 2, 'position' => 2, 'text' => 'Lima']),
        ]));

        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1, 'title' => 'Prova de Geografia']);
        $exam->setRelation('questions', new Collection([$brazil, $argentina]));

        $attempt = Attempt::factory()->scored(1, 2)->make([
            'id' => 7,
            'exam_id' => 10,
            'student_id' => 3,
            'submitted_at' => Carbon::parse('2026-10-16 10:00:00'),
        ]);
        $attempt->setRelation('exam', $exam);
        $attempt->setRelation('answers', new Collection([
            new AttemptAnswer(['question_id' => 1, 'option_id' => 11, 'is_correct' => false]),
            new AttemptAnswer(['question_id' => 2, 'option_id' => 21, 'is_correct' => true]),
        ]));

        return $attempt;
    }
}
