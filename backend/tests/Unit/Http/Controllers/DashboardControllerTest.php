<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\PaginationRequest;
use App\Http\Services\Dashboard\GetDashboardExamsRankingService;
use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Interfaces\DashboardRepositoryInterface;
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
            $mock->shouldReceive('totals')->once()->with(1)
                ->andReturn(['total_attempts' => 0, 'total_students' => 0, 'average_percentage' => null]);
            $mock->shouldReceive('bestAttempt')->once()->andReturn(null);
            $mock->shouldReceive('worstAttempt')->once()->andReturn(null);
            $mock->shouldReceive('topStudent')->once()->andReturn(null);
            $mock->shouldReceive('examStats')->once()->with(1)->andReturn([]);
        });

        $response = (new DashboardController)->summary($this->teacher, new GetDashboardSummaryService($dashboard));

        $this->assertSame('Dashboard carregado com sucesso.', $response->getData(true)['message']);
        $this->assertSame(0, $response->getData(true)['data']['total_attempts']);
    }

    public function test_exams_ranking_returns_paginated_exams(): void
    {
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examsRanking')->once()->with(1, 1, 4)->andReturn(new LengthAwarePaginator([[
                'position' => 1,
                'exam_id' => 12,
                'title' => 'Prova de Química',
                'students_count' => 4,
                'average_percentage' => '83.33',
                'best_student_name' => 'Ana Souza',
            ]], 1, 4, 1));
        });

        $response = (new DashboardController)->examsRanking(
            $this->validatedRequest(PaginationRequest::class, ['per_page' => 4], 'GET'),
            $this->teacher,
            new GetDashboardExamsRankingService($dashboard),
        );

        $data = $response->getData(true);
        $this->assertSame('Ranking das provas carregado com sucesso.', $data['message']);
        $this->assertSame('Ana Souza', $data['data']['items'][0]['best_student_name']);
        $this->assertSame(['current_page' => 1, 'last_page' => 1, 'per_page' => 4, 'total' => 1], $data['data']['meta']);
    }
}
