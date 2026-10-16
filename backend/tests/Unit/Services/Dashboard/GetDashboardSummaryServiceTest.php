<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $service = new GetDashboardSummaryService($this->dashboardRepository(times: 1), $this->examRepository());

        $summary = $service->handle($this->teacher, null);

        $this->assertSame([
            'exam_id' => null,
            'totals' => ['total_attempts' => 2, 'total_students' => 2, 'average_percentage' => '75.00'],
            'best' => ['student_name' => 'Ana Souza', 'percentage' => '100.00'],
            'worst' => ['student_name' => 'Bruno Lima', 'percentage' => '50.00'],
            'exams' => [['id' => 10, 'title' => 'Prova de Geografia']],
        ], $summary);
    }

    public function test_second_call_comes_from_cache(): void
    {
        $service = new GetDashboardSummaryService($this->dashboardRepository(times: 1), $this->examRepository());

        $first = $service->handle($this->teacher, null);
        $second = $service->handle($this->teacher, null);

        $this->assertSame($first, $second);
        $this->assertNotNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary:exam:all'));
    }

    public function test_uses_one_cache_entry_per_exam_filter(): void
    {
        $exams = $this->examRepository(function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->twice()->with($this->teacher, 10)
                ->andReturn(Exam::factory()->make(['id' => 10, 'teacher_id' => 1]));
        });
        $service = new GetDashboardSummaryService($this->dashboardRepository(times: 2), $exams);

        $service->handle($this->teacher, null);
        $service->handle($this->teacher, 10);
        $filtered = $service->handle($this->teacher, 10);

        $this->assertSame(10, $filtered['exam_id']);
        $this->assertNotNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary:exam:10'));
    }

    public function test_rejects_exam_of_another_teacher_before_reading_metrics(): void
    {
        $exams = $this->examRepository(function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
        });
        $dashboard = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('totals');
        });

        $this->expectException(ModelNotFoundException::class);

        (new GetDashboardSummaryService($dashboard, $exams))->handle($this->teacher, 99);
    }

    private function dashboardRepository(int $times): DashboardRepositoryInterface
    {
        return Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) use ($times) {
            $mock->shouldReceive('totals')->times($times)
                ->andReturn(['total_attempts' => 2, 'total_students' => 2, 'average_percentage' => '75.00']);
            $mock->shouldReceive('bestAttempt')->times($times)
                ->andReturn(['student_name' => 'Ana Souza', 'percentage' => '100.00']);
            $mock->shouldReceive('worstAttempt')->times($times)
                ->andReturn(['student_name' => 'Bruno Lima', 'percentage' => '50.00']);
            $mock->shouldReceive('examStats')->times($times)->with(1)
                ->andReturn([['id' => 10, 'title' => 'Prova de Geografia']]);
        });
    }

    private function examRepository(?callable $expectations = null): ExamRepositoryInterface
    {
        return Mockery::mock(ExamRepositoryInterface::class, $expectations ?? function (MockInterface $mock) {
            $mock->shouldNotReceive('findForTeacher');
        });
    }
}
