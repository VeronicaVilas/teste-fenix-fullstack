<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetStudentDashboardService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetStudentDashboardServiceTest extends TestCase
{
    public function test_reads_the_student_summary_from_repository(): void
    {
        $student = User::factory()->student()->make(['id' => 3]);
        $summary = ['answered_count' => 3, 'pending_count' => 5, 'average_percentage' => '41.67', 'best_percentage' => '75.00'];

        $repository = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) use ($summary) {
            $mock->shouldReceive('studentSummary')->once()->with(3)->andReturn($summary);
        });

        $this->assertSame($summary, (new GetStudentDashboardService($repository))->handle($student));
    }
}
