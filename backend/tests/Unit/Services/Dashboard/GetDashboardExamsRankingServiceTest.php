<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetDashboardExamsRankingService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetDashboardExamsRankingServiceTest extends TestCase
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
        $repository = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examsRanking')->once()->with(1, 2, 4)
                ->andReturn(new LengthAwarePaginator([['position' => 5], ['position' => 6]], 6, 4, 2));
        });

        $ranking = (new GetDashboardExamsRankingService($repository))->handle($this->teacher, 2, 4);

        $this->assertSame([['position' => 5], ['position' => 6]], $ranking->items());
        $this->assertSame(6, $ranking->total());
        $this->assertSame(2, $ranking->currentPage());
        $this->assertSame(2, $ranking->lastPage());
    }

    public function test_caches_each_page_separately(): void
    {
        $repository = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('examsRanking')->once()->with(1, 1, 4)
                ->andReturn(new LengthAwarePaginator([['position' => 1]], 5, 4, 1));
            $mock->shouldReceive('examsRanking')->once()->with(1, 2, 4)
                ->andReturn(new LengthAwarePaginator([['position' => 5]], 5, 4, 2));
        });
        $service = new GetDashboardExamsRankingService($repository);

        $service->handle($this->teacher, 1, 4);
        $service->handle($this->teacher, 2, 4);
        $cachedFirstPage = $service->handle($this->teacher, 1, 4);

        $this->assertSame([['position' => 1]], $cachedFirstPage->items());
        $this->assertNotNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:exams-ranking:page:2:per:4'));
    }
}
