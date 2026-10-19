<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\SearchPaginationRequest;
use App\Http\Services\Dashboard\GetDashboardExamAveragesService;
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

    public function test_exam_averages_returns_paginated_exams(): void
    {
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examAverages')->once()->with(1, 'geo', 2, 4)->andReturn(new LengthAwarePaginator([[
                'id' => '6',
                'title' => 'Prova de Geografia',
                'attempts_count' => '4',
                'average_percentage' => '62.5',
            ]], 5, 4, 2));
        });

        $response = (new DashboardController)->examAverages(
            $this->validatedRequest(SearchPaginationRequest::class, ['search' => ' geo ', 'page' => 2, 'per_page' => 4], 'GET'),
            $this->teacher,
            new GetDashboardExamAveragesService($dashboard),
        );

        $data = $response->getData(true);
        $this->assertSame('Médias das provas carregadas com sucesso.', $data['message']);
        $this->assertSame([['id' => 6, 'title' => 'Prova de Geografia', 'attempts_count' => 4, 'average_percentage' => '62.50']], $data['data']['items']);
        $this->assertSame(['current_page' => 2, 'last_page' => 2, 'per_page' => 4, 'total' => 5], $data['data']['meta']);
    }
}
