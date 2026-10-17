<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery\MockInterface;
use Tests\TestCase;

class DashboardSummaryTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/dashboard';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_returns_summary_with_best_worst_and_per_exam_average(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('totals')->once()->with(1, null)
                ->andReturn(['total_attempts' => 3, 'total_students' => 3, 'average_percentage' => '66.666']);
            $mock->shouldReceive('bestAttempt')->once()->with(1, null)->andReturn([
                'student_name' => 'Ana Souza',
                'exam_title' => 'Prova de Geografia',
                'score' => 3,
                'total_questions' => 3,
                'percentage' => '100.00',
                'submitted_at' => '2026-10-16 10:00:00',
            ]);
            $mock->shouldReceive('worstAttempt')->once()->with(1, null)->andReturn([
                'student_name' => 'Bruno Lima',
                'exam_title' => 'Prova de Geografia',
                'score' => 1,
                'total_questions' => 3,
                'percentage' => 33.33,
                'submitted_at' => '2026-10-16 11:00:00',
            ]);
            $mock->shouldReceive('topStudent')->once()->with(1, null)
                ->andReturn(['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '18.5']);
            $mock->shouldReceive('examStats')->once()->with(1)->andReturn([
                ['id' => 10, 'title' => 'Prova de Geografia', 'attempts_count' => 3, 'average_percentage' => '66.67', 'best_percentage' => '100.00', 'worst_percentage' => '33.33'],
                ['id' => 11, 'title' => 'Prova de História', 'attempts_count' => 0, 'average_percentage' => null, 'best_percentage' => null, 'worst_percentage' => null],
            ]);
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Dashboard carregado com sucesso.',
                'status' => 200,
                'data' => [
                    'exam_id' => null,
                    'total_attempts' => 3,
                    'total_students' => 3,
                    'average_percentage' => '66.67',
                    'best' => [
                        'student_name' => 'Ana Souza',
                        'exam_title' => 'Prova de Geografia',
                        'score' => 3,
                        'total_questions' => 3,
                        'percentage' => '100.00',
                        'submitted_at' => '2026-10-16T10:00:00+00:00',
                    ],
                    'worst' => [
                        'student_name' => 'Bruno Lima',
                        'exam_title' => 'Prova de Geografia',
                        'score' => 1,
                        'total_questions' => 3,
                        'percentage' => '33.33',
                        'submitted_at' => '2026-10-16T11:00:00+00:00',
                    ],
                    'top_student' => ['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '18.50'],
                    'exams' => [
                        ['id' => 10, 'title' => 'Prova de Geografia', 'attempts_count' => 3, 'average_percentage' => '66.67', 'best_percentage' => '100.00', 'worst_percentage' => '33.33'],
                        ['id' => 11, 'title' => 'Prova de História', 'attempts_count' => 0, 'average_percentage' => null, 'best_percentage' => null, 'worst_percentage' => null],
                    ],
                ],
            ]);
    }

    public function test_returns_empty_metrics_without_attempts(): void
    {
        $this->mockEmptyDashboard();

        $this->getJson(self::URL)
            ->assertOk()
            ->assertJsonPath('data.total_attempts', 0)
            ->assertJsonPath('data.average_percentage', null)
            ->assertJsonPath('data.best', null)
            ->assertJsonPath('data.worst', null)
            ->assertJsonPath('data.top_student', null);
    }

    public function test_filters_by_exam_of_the_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()
                ->withArgs(fn (User $teacher, int $examId) => $teacher->id === 1 && $examId === 10)
                ->andReturn(Exam::factory()->make(['id' => 10, 'teacher_id' => 1]));
        });
        $this->mockEmptyDashboard(examId: 10);

        $this->getJson(self::URL.'?exam_id=10')
            ->assertOk()
            ->assertJsonPath('data.exam_id', 10);
    }

    public function test_returns_404_for_exam_of_another_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
        });
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('totals');
        });

        $this->getJson(self::URL.'?exam_id=99')->assertNotFound();
    }

    public function test_validates_exam_filter(): void
    {
        $this->getJson(self::URL.'?exam_id=abc')
            ->assertUnprocessable()
            ->assertJsonPath('errors.exam_id.0', 'A prova deve ser informada pelo id numérico.');
    }

    private function mockEmptyDashboard(?int $examId = null): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) use ($examId) {
            $mock->shouldReceive('totals')->once()->with(1, $examId)
                ->andReturn(['total_attempts' => 0, 'total_students' => 0, 'average_percentage' => null]);
            $mock->shouldReceive('bestAttempt')->once()->with(1, $examId)->andReturn(null);
            $mock->shouldReceive('worstAttempt')->once()->with(1, $examId)->andReturn(null);
            $mock->shouldReceive('topStudent')->once()->with(1, $examId)->andReturn(null);
            $mock->shouldReceive('examStats')->once()->with(1)->andReturn([]);
        });
    }
}
