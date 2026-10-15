<?php

namespace Tests\Feature\Exam;

use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;
use Tests\TestCase;

class ListTeacherExamsTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/exams';

    public function test_lists_teacher_exams_with_counts_and_pagination(): void
    {
        $teacher = $this->mockTeacher();

        $draft = Exam::factory()->make([
            'id' => 2,
            'teacher_id' => 1,
            'title' => 'Prova de História',
            'created_at' => Carbon::parse('2026-10-15 12:00:00'),
        ]);
        $draft->setAttribute('questions_count', 5);
        $draft->setAttribute('attempts_count', 0);

        $published = Exam::factory()->published()->make([
            'id' => 1,
            'teacher_id' => 1,
            'title' => 'Prova de Geografia',
            'published_at' => Carbon::parse('2026-10-15 10:00:00'),
            'created_at' => Carbon::parse('2026-10-15 09:00:00'),
        ]);
        $published->setAttribute('questions_count', 3);
        $published->setAttribute('attempts_count', 4);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($teacher, $draft, $published) {
            $mock->shouldReceive('paginateByTeacher')->once()
                ->withArgs(fn (User $user, ?ExamStatus $status, int $perPage) => $user->is($teacher) && $status === null && $perPage === 10)
                ->andReturn(new LengthAwarePaginator([$draft, $published], 12, 10, 1));
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Provas listadas com sucesso.',
                'status' => 200,
                'data' => [
                    'items' => [
                        [
                            'id' => 2,
                            'title' => 'Prova de História',
                            'status' => 'draft',
                            'published_at' => null,
                            'questions_count' => 5,
                            'attempts_count' => 0,
                            'created_at' => '2026-10-15T12:00:00+00:00',
                        ],
                        [
                            'id' => 1,
                            'title' => 'Prova de Geografia',
                            'status' => 'published',
                            'published_at' => '2026-10-15T10:00:00+00:00',
                            'questions_count' => 3,
                            'attempts_count' => 4,
                            'created_at' => '2026-10-15T09:00:00+00:00',
                        ],
                    ],
                    'meta' => ['current_page' => 1, 'last_page' => 2, 'per_page' => 10, 'total' => 12],
                ],
            ]);
    }

    public function test_forwards_status_filter_and_page_size(): void
    {
        $this->mockTeacher();

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('paginateByTeacher')->once()
                ->withArgs(fn (User $user, ?ExamStatus $status, int $perPage) => $status === ExamStatus::Published && $perPage === 25)
                ->andReturn(new LengthAwarePaginator([], 0, 25, 1));
        });

        $this->getJson(self::URL.'?status=published&per_page=25')
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.meta.per_page', 25);
    }

    public function test_rejects_invalid_filters(): void
    {
        $this->mockTeacher();
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('paginateByTeacher');
        });

        $this->getJson(self::URL.'?status=archived')
            ->assertUnprocessable()
            ->assertJsonPath('errors.status.0', 'O status deve ser draft ou published.');

        $this->getJson(self::URL.'?per_page=100')
            ->assertUnprocessable()
            ->assertJsonPath('errors.per_page.0', 'A quantidade por página deve ser no máximo 50.');

        $this->getJson(self::URL.'?page=0')
            ->assertUnprocessable()
            ->assertJsonPath('errors.page.0', 'A página deve ser no mínimo 1.');
    }

    public function test_returns_404_for_unknown_teacher(): void
    {
        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)->andThrow(new ModelNotFoundException);
        });

        $this->getJson(self::URL)->assertNotFound();
    }

    private function mockTeacher(): User
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) use ($teacher) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)->andReturn($teacher);
        });

        return $teacher;
    }
}
