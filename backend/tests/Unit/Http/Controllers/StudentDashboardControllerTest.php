<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\StudentDashboardController;
use App\Http\Services\Dashboard\GetStudentDashboardService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class StudentDashboardControllerTest extends TestCase
{
    public function test_summary_returns_the_student_metrics(): void
    {
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('studentSummary')->once()->with(3)->andReturn([
                'answered_count' => 2,
                'pending_count' => 1,
                'average_percentage' => '65.00',
                'best_percentage' => '80.00',
            ]);
        });

        $response = (new StudentDashboardController)->summary(
            User::factory()->student()->make(['id' => 3]),
            new GetStudentDashboardService($dashboard),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Dashboard carregado com sucesso.',
            'status' => 200,
            'data' => ['answered_count' => 2, 'pending_count' => 1, 'average_percentage' => '65.00', 'best_percentage' => '80.00'],
        ], $response->getData(true));
    }
}
