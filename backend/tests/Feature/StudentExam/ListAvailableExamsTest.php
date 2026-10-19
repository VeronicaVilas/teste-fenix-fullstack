<?php

namespace Tests\Feature\StudentExam;

use App\Enums\StudentExamStatus;
use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class ListAvailableExamsTest extends TestCase
{
    private const URL = '/api/v1/students/3/exams';

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->student()->make(['id' => 3]);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(3, UserRole::Student)->andReturn($this->student);
        });
    }

    public function test_lists_published_exams_with_the_student_attempt_status(): void
    {
        $answered = $this->publishedExam(3, 'Prova de Geografia', 5, '2026-10-15 12:00:00');
        $answered->setRelation('attempts', new Collection([
            Attempt::factory()->scored(4, 5)->make([
                'exam_id' => 3,
                'student_id' => 3,
                'submitted_at' => Carbon::parse('2026-10-15 13:00:00'),
            ]),
        ]));

        $pending = $this->publishedExam(4, 'Prova de História', 8, '2026-10-15 10:00:00');
        $pending->setRelation('attempts', new Collection);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($answered, $pending) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()
                ->withArgs(fn (User $student, ?StudentExamStatus $status, ?string $search, int $perPage) => $student->is($this->student)
                    && $status === null && $search === null && $perPage === 10)
                ->andReturn(new LengthAwarePaginator([$answered, $pending], 2, 10, 1));
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Provas disponíveis listadas com sucesso.',
                'status' => 200,
                'data' => [
                    'items' => [
                        [
                            'id' => 3,
                            'title' => 'Prova de Geografia',
                            'description' => null,
                            'questions_count' => 5,
                            'published_at' => '2026-10-15T12:00:00+00:00',
                            'answered' => true,
                            'attempt' => [
                                'score' => 4,
                                'total_questions' => 5,
                                'percentage' => '80.00',
                                'submitted_at' => '2026-10-15T13:00:00+00:00',
                            ],
                        ],
                        [
                            'id' => 4,
                            'title' => 'Prova de História',
                            'description' => null,
                            'questions_count' => 8,
                            'published_at' => '2026-10-15T10:00:00+00:00',
                            'answered' => false,
                            'attempt' => null,
                        ],
                    ],
                    'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 10, 'total' => 2],
                ],
            ]);
    }

    public function test_forwards_page_size_and_validates_it(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()
                ->withArgs(fn (User $student, ?StudentExamStatus $status, ?string $search, int $perPage) => $perPage === 5)
                ->andReturn(new LengthAwarePaginator([], 0, 5, 1));
        });

        $this->getJson(self::URL.'?per_page=5')->assertOk();

        $this->getJson(self::URL.'?per_page=51')
            ->assertUnprocessable()
            ->assertJsonPath('errors.per_page.0', 'A quantidade por página deve ser no máximo 50.');
    }

    public function test_forwards_status_and_search_filters(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()
                ->withArgs(fn (User $student, ?StudentExamStatus $status, ?string $search, int $perPage) => $status === StudentExamStatus::Pending
                    && $search === 'Geografia' && $perPage === 4)
                ->andReturn(new LengthAwarePaginator([], 0, 4, 1));
        });

        $this->getJson(self::URL.'?status=pending&search=%20Geografia%20&per_page=4')->assertOk();
    }

    public function test_ignores_an_empty_search(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()
                ->withArgs(fn (User $student, ?StudentExamStatus $status, ?string $search) => $status === StudentExamStatus::Answered && $search === null)
                ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        });

        $this->getJson(self::URL.'?status=answered&search=%20%20')->assertOk();
    }

    public function test_validates_the_filters(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('paginatePublishedForStudent');
        });

        $this->getJson(self::URL.'?status=archived')
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'A situação deve ser pending ou answered.');

        $this->getJson(self::URL.'?search='.str_repeat('a', 256))
            ->assertUnprocessable()
            ->assertJsonPath('errors.search.0', 'A busca deve ter no máximo 255 caracteres.');
    }

    public function test_returns_404_when_user_is_not_a_student(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Student)->andThrow(new ModelNotFoundException);
        });
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('paginatePublishedForStudent');
        });

        $this->getJson('/api/v1/students/1/exams')->assertNotFound();
    }

    private function publishedExam(int $id, string $title, int $questions, string $publishedAt): Exam
    {
        $exam = Exam::factory()->published()->make([
            'id' => $id,
            'teacher_id' => 1,
            'title' => $title,
            'description' => null,
            'published_at' => Carbon::parse($publishedAt),
        ]);
        $exam->setAttribute('questions_count', $questions);

        return $exam;
    }
}
