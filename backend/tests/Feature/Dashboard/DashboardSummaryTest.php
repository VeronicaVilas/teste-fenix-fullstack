<?php

namespace Tests\Feature\Dashboard;

use App\Enums\UserRole;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
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

    public function test_returns_summary_with_best_worst_and_top_student(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('totals')->once()->with(1)
                ->andReturn(['total_attempts' => 3, 'total_students' => 3, 'average_percentage' => '66.666']);
            $mock->shouldReceive('bestAttempt')->once()->with(1)->andReturn([
                'student_name' => 'Ana Souza',
                'exam_title' => 'Prova de Geografia',
                'score' => 3,
                'total_questions' => 3,
                'percentage' => '100.00',
                'submitted_at' => '2026-10-16 10:00:00',
            ]);
            $mock->shouldReceive('worstAttempt')->once()->with(1)->andReturn([
                'student_name' => 'Bruno Lima',
                'exam_title' => 'Prova de Geografia',
                'score' => 1,
                'total_questions' => 3,
                'percentage' => 33.33,
                'submitted_at' => '2026-10-16 11:00:00',
            ]);
            $mock->shouldReceive('topStudent')->once()->with(1)
                ->andReturn(['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '18.5']);
        });

        $this->getJson(self::URL)
            ->assertOk()
            ->assertExactJson([
                'message' => 'Dashboard carregado com sucesso.',
                'status' => 200,
                'data' => [
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

    private function mockEmptyDashboard(): void
    {
        $this->mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('totals')->once()->with(1)
                ->andReturn(['total_attempts' => 0, 'total_students' => 0, 'average_percentage' => null]);
            $mock->shouldReceive('bestAttempt')->once()->with(1)->andReturn(null);
            $mock->shouldReceive('worstAttempt')->once()->with(1)->andReturn(null);
            $mock->shouldReceive('topStudent')->once()->with(1)->andReturn(null);
        });
    }
}
