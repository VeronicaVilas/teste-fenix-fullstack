<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetDashboardRankingService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetDashboardRankingServiceTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'cache.stores.array.serialize' => true]);
        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
    }

    public function test_returns_the_page_from_repository(): void
    {
        $service = new GetDashboardRankingService(
            $this->dashboardRepository(fn (MockInterface $mock) => $mock->shouldReceive('ranking')->once()->with(1, null, 2, 2)
                ->andReturn($this->page([['position' => 3], ['position' => 4]], total: 5, page: 2))),
            $this->examRepository(),
        );

        $ranking = $service->handle($this->teacher, null, 2, 2);

        $this->assertSame([['position' => 3], ['position' => 4]], $ranking->items());
        $this->assertSame(5, $ranking->total());
        $this->assertSame(2, $ranking->currentPage());
        $this->assertSame(3, $ranking->lastPage());
    }

    public function test_caches_each_page_separately(): void
    {
        $service = new GetDashboardRankingService(
            $this->dashboardRepository(function (MockInterface $mock) {
                $mock->shouldReceive('ranking')->once()->with(1, null, 1, 10)->andReturn($this->page([['position' => 1]], total: 11, page: 1));
                $mock->shouldReceive('ranking')->once()->with(1, null, 2, 10)->andReturn($this->page([['position' => 11]], total: 11, page: 2));
            }),
            $this->examRepository(),
        );

        $service->handle($this->teacher, null, 1, 10);
        $service->handle($this->teacher, null, 2, 10);
        $cachedFirstPage = $service->handle($this->teacher, null, 1, 10);

        $this->assertSame([['position' => 1]], $cachedFirstPage->items());
        $this->assertNotNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:ranking:exam:all:page:2:per:10'));
    }

    public function test_validates_exam_before_reading_ranking(): void
    {
        $exams = $this->examRepository(function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->with($this->teacher, 10)
                ->andReturn(Exam::factory()->make(['id' => 10, 'teacher_id' => 1]));
        });
        $service = new GetDashboardRankingService(
            $this->dashboardRepository(fn (MockInterface $mock) => $mock->shouldReceive('ranking')->once()->with(1, 10, 1, 10)
                ->andReturn($this->page([], total: 0, page: 1))),
            $exams,
        );

        $this->assertSame(0, $service->handle($this->teacher, 10, 1, 10)->total());
    }

    public function test_rejects_exam_of_another_teacher(): void
    {
        $exams = $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException));
        $service = new GetDashboardRankingService(
            $this->dashboardRepository(fn (MockInterface $mock) => $mock->shouldNotReceive('ranking')),
            $exams,
        );

        $this->expectException(ModelNotFoundException::class);

        $service->handle($this->teacher, 99, 1, 10);
    }

    private function page(array $items, int $total, int $page): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, $total, count($items) ?: 10, $page);
    }

    private function dashboardRepository(callable $expectations): DashboardRepositoryInterface
    {
        return Mockery::mock(DashboardRepositoryInterface::class, $expectations);
    }

    private function examRepository(?callable $expectations = null): ExamRepositoryInterface
    {
        return Mockery::mock(ExamRepositoryInterface::class, $expectations ?? function (MockInterface $mock) {
            $mock->shouldNotReceive('findForTeacher');
        });
    }
}
