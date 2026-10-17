<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\DashboardFilterRequest;
use App\Http\Services\Dashboard\GetDashboardRankingService;
use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use BuildsFormRequests;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
    }

    public function test_summary_returns_metrics(): void
    {
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('totals')->once()->with(1, null)
                ->andReturn(['total_attempts' => 0, 'total_students' => 0, 'average_percentage' => null]);
            $mock->shouldReceive('bestAttempt')->once()->andReturn(null);
            $mock->shouldReceive('worstAttempt')->once()->andReturn(null);
            $mock->shouldReceive('topStudent')->once()->andReturn(null);
            $mock->shouldReceive('examStats')->once()->with(1)->andReturn([]);
        });

        $response = (new DashboardController)->summary(
            $this->validatedRequest(DashboardFilterRequest::class, [], 'GET'),
            $this->teacher,
            new GetDashboardSummaryService($dashboard, $this->examRepository()),
        );

        $this->assertSame('Dashboard carregado com sucesso.', $response->getData(true)['message']);
        $this->assertSame(0, $response->getData(true)['data']['total_attempts']);
    }

    public function test_ranking_returns_paginated_items(): void
    {
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('ranking')->once()->with(1, null, 2, 5)->andReturn(new LengthAwarePaginator([[
                'position' => 6,
                'attempt_id' => 9,
                'student_name' => 'Ana Souza',
                'exam_title' => 'Prova de Geografia',
                'score' => 3,
                'total_questions' => 3,
                'percentage' => '100.00',
                'submitted_at' => '2026-10-16 10:00:00',
            ]], 6, 5, 2));
        });

        $response = (new DashboardController)->ranking(
            $this->validatedRequest(DashboardFilterRequest::class, ['page' => 2, 'per_page' => 5], 'GET'),
            $this->teacher,
            new GetDashboardRankingService($dashboard, $this->examRepository()),
        );

        $data = $response->getData(true);
        $this->assertSame('Ranking carregado com sucesso.', $data['message']);
        $this->assertSame(6, $data['data']['items'][0]['position']);
        $this->assertSame(['current_page' => 2, 'last_page' => 2, 'per_page' => 5, 'total' => 6], $data['data']['meta']);
    }

    private function examRepository(): ExamRepositoryInterface
    {
        return Mockery::mock(ExamRepositoryInterface::class, fn (MockInterface $mock) => $mock->shouldNotReceive('findForTeacher'));
    }
}
