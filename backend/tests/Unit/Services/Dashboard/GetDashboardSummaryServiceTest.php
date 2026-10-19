<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetDashboardSummaryServiceTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'cache.stores.array.serialize' => true]);
        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
    }

    public function test_collects_metrics_from_repository(): void
    {
        $service = new GetDashboardSummaryService($this->dashboardRepository());

        $this->assertSame([
            'totals' => ['total_attempts' => 2, 'total_students' => 2, 'average_percentage' => '75.00'],
            'best' => ['student_name' => 'Ana Souza', 'percentage' => '100.00'],
            'worst' => ['student_name' => 'Bruno Lima', 'percentage' => '50.00'],
            'top_student' => ['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '15.00'],
        ], $service->handle($this->teacher));
    }

    public function test_second_call_comes_from_cache(): void
    {
        $service = new GetDashboardSummaryService($this->dashboardRepository());

        $first = $service->handle($this->teacher);
        $second = $service->handle($this->teacher);

        $this->assertSame($first, $second);
        $this->assertNotNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary'));
    }

    private function dashboardRepository(): DashboardRepositoryInterface
    {
        return Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('totals')->once()->with(1)
                ->andReturn(['total_attempts' => 2, 'total_students' => 2, 'average_percentage' => '75.00']);
            $mock->shouldReceive('bestAttempt')->once()->with(1)
                ->andReturn(['student_name' => 'Ana Souza', 'percentage' => '100.00']);
            $mock->shouldReceive('worstAttempt')->once()->with(1)
                ->andReturn(['student_name' => 'Bruno Lima', 'percentage' => '50.00']);
            $mock->shouldReceive('topStudent')->once()->with(1)
                ->andReturn(['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '15.00']);
        });
    }
}
