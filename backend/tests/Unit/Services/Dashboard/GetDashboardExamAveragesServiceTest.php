<?php

namespace Tests\Unit\Services\Dashboard;

use App\Http\Services\Dashboard\GetDashboardExamAveragesService;
use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetDashboardExamAveragesServiceTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array', 'cache.stores.array.serialize' => true]);
        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
    }

    public function test_returns_the_page_and_caches_it_under_the_dashboard_tag(): void
    {
        $item = ['id' => 6, 'title' => 'Prova de Geografia', 'attempts_count' => 4, 'average_percentage' => '62.50'];
        $service = new GetDashboardExamAveragesService($this->repository(times: 1, search: 'Geo', item: $item));

        $first = $service->handle($this->teacher, 'Geo', 2, 4);
        $second = $service->handle($this->teacher, 'Geo', 2, 4);

        $this->assertSame([$item], $first->items());
        $this->assertSame(5, $first->total());
        $this->assertSame(2, $first->currentPage());
        $this->assertSame(4, $first->perPage());
        $this->assertEquals($first->items(), $second->items());

        $key = sprintf('dashboard:teacher:1:exam-averages:search:%s:page:2:per:4', md5('geo'));
        $this->assertNotNull(Cache::tags(['dashboard'])->get($key));
    }

    public function test_keeps_one_cache_entry_per_search(): void
    {
        $item = ['id' => 6, 'title' => 'Prova de Geografia', 'attempts_count' => 4, 'average_percentage' => '62.50'];
        $repository = Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) use ($item) {
            $mock->shouldReceive('examAverages')->once()->with(1, null, 1, 4)->andReturn(new LengthAwarePaginator([$item], 1, 4, 1));
            $mock->shouldReceive('examAverages')->once()->with(1, 'Geo', 1, 4)->andReturn(new LengthAwarePaginator([$item], 1, 4, 1));
        });
        $service = new GetDashboardExamAveragesService($repository);

        $service->handle($this->teacher, null, 1, 4);
        $service->handle($this->teacher, 'Geo', 1, 4);
        $service->handle($this->teacher, null, 1, 4);

        $this->assertNotNull(Cache::tags(['dashboard'])->get(sprintf('dashboard:teacher:1:exam-averages:search:%s:page:1:per:4', md5(''))));
    }

    private function repository(int $times, ?string $search, array $item): DashboardRepositoryInterface
    {
        return Mockery::mock(DashboardRepositoryInterface::class, function (MockInterface $mock) use ($times, $search, $item) {
            $mock->shouldReceive('examAverages')->times($times)->with(1, $search, 2, 4)
                ->andReturn(new LengthAwarePaginator([$item], 5, 4, 2));
        });
    }
}
