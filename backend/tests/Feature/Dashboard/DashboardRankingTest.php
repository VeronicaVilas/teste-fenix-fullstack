<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardRankingTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/dashboard/ranking';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_returns_ranking_page_with_continuous_positions(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('ranking')->once()->with(1, null, 2, 2)->andReturn(new LengthAwarePaginator([
                $this->row(position: 3, attemptId: 9, student: 'Carla Mendes', score: 2, percentage: '66.67', submittedAt: '2026-10-16 09:00:00'),
                $this->row(position: 4, attemptId: 4, student: 'Bruno Lima', score: 1, percentage: 33.33, submittedAt: '2026-10-16 10:00:00'),
            ], 5, 2, 2));
        });

        $this->getJson(self::URL.'?page=2&per_page=2')
            ->assertOk()
            ->assertExactJson([
                'message' => 'Ranking carregado com sucesso.',
                'status' => 200,
                'data' => [
                    'items' => [
                        [
                            'position' => 3,
                            'attempt_id' => 9,
                            'student_name' => 'Carla Mendes',
                            'exam_title' => 'Prova de Geografia',
                            'score' => 2,
                            'total_questions' => 3,
                            'percentage' => '66.67',
                            'submitted_at' => '2026-10-16T09:00:00+00:00',
                        ],
                        [
                            'position' => 4,
                            'attempt_id' => 4,
                            'student_name' => 'Bruno Lima',
                            'exam_title' => 'Prova de Geografia',
                            'score' => 1,
                            'total_questions' => 3,
                            'percentage' => '33.33',
                            'submitted_at' => '2026-10-16T10:00:00+00:00',
                        ],
                    ],
                    'meta' => ['current_page' => 2, 'last_page' => 3, 'per_page' => 2, 'total' => 5],
                ],
            ]);
    }

    public function test_uses_first_page_and_ten_items_by_default(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('ranking')->once()->with(1, null, 1, 10)
                ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.items', [])
            ->assertJsonPath('data.meta.per_page', 10);
    }

    public function test_filters_by_exam_of_the_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()
                ->andReturn(Exam::factory()->make(['id' => 10, 'teacher_id' => 1]));
        });
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('ranking')->once()->with(1, 10, 1, 10)
                ->andReturn(new LengthAwarePaginator([], 0, 10, 1));
        });

        $this->getJson(self::URL.'?exam_id=10')->assertOk();
    }

    public function test_returns_404_for_exam_of_another_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
        });
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('ranking');
        });

        $this->getJson(self::URL.'?exam_id=99')->assertNotFound();
    }

    public function test_validates_pagination(): void
    {
        $this->getJson(self::URL.'?per_page=100')
            ->assertUnprocessable()
            ->assertJsonPath('errors.per_page.0', 'A quantidade por página deve ser no máximo 50.');
    }

    private function row(int $position, int $attemptId, string $student, int $score, float|string $percentage, string $submittedAt): array
    {
        return [
            'position' => $position,
            'attempt_id' => $attemptId,
            'student_name' => $student,
            'exam_title' => 'Prova de Geografia',
            'score' => $score,
            'total_questions' => 3,
            'percentage' => $percentage,
            'submitted_at' => $submittedAt,
        ];
    }
}
