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
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class ShowTeacherExamTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->make(['id' => 1]);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)->andReturn($this->teacher);
        });
    }

    public function test_shows_exam_with_answer_key(): void
    {
        $exam = Exam::factory()->published()->make([
            'id' => 10,
            'teacher_id' => 1,
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'published_at' => Carbon::parse('2026-10-15 10:00:00'),
            'created_at' => Carbon::parse('2026-10-15 09:00:00'),
            'updated_at' => Carbon::parse('2026-10-15 10:00:00'),
        ]);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findForTeacher')->once()
                ->withArgs(fn (User $teacher, int $examId) => $teacher->is($this->teacher) && $examId === 10)
                ->andReturn($exam);
            $mock->shouldReceive('loadDetails')->once()->with($exam)->andReturn($this->withQuestions($exam));
        });

        $this->getJson('/api/v1/teachers/1/exams/10')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Prova encontrada.',
                'status' => 200,
                'data' => [
                    'id' => 10,
                    'title' => 'Prova de Geografia',
                    'description' => 'Capitais',
                    'status' => 'published',
                    'published_at' => '2026-10-15T10:00:00+00:00',
                    'questions_count' => 1,
                    'attempts_count' => 3,
                    'questions' => [
                        [
                            'id' => 20,
                            'position' => 1,
                            'statement' => 'Qual é a capital do Brasil?',
                            'options' => [
                                ['id' => 31, 'position' => 1, 'text' => 'São Paulo', 'is_correct' => false],
                                ['id' => 32, 'position' => 2, 'text' => 'Brasília', 'is_correct' => true],
                            ],
                        ],
                    ],
                    'created_at' => '2026-10-15T09:00:00+00:00',
                    'updated_at' => '2026-10-15T10:00:00+00:00',
                ],
            ]);
    }

    public function test_returns_404_for_exam_of_another_teacher_or_missing(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
            $mock->shouldNotReceive('loadDetails');
        });

        $this->getJson('/api/v1/teachers/1/exams/99')
            ->assertNotFound()
            ->assertJsonPath('message', 'Recurso não encontrado.');
    }

    public function test_non_numeric_exam_id_returns_404(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('findForTeacher');
        });

        $this->getJson('/api/v1/teachers/1/exams/abc')->assertNotFound();
    }

    private function withQuestions(Exam $exam): Exam
    {
        $question = Question::factory()->make(['id' => 20, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é a capital do Brasil?']);
        $question->setRelation('options', new Collection([
            Option::factory()->make(['id' => 31, 'question_id' => 20, 'position' => 1, 'text' => 'São Paulo']),
            Option::factory()->correct()->make(['id' => 32, 'question_id' => 20, 'position' => 2, 'text' => 'Brasília']),
        ]));

        $exam->setRelation('questions', new Collection([$question]));
        $exam->setAttribute('questions_count', 1);
        $exam->setAttribute('attempts_count', 3);

        return $exam;
    }
}
