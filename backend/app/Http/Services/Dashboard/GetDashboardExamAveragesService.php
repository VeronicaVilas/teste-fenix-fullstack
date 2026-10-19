<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final readonly class GetDashboardExamAveragesService
{
    public function __construct(private DashboardRepositoryInterface $dashboardRepository) {}

    public function handle(User $teacher, ?string $search, int $page, int $perPage): LengthAwarePaginatorContract
    {
        $key = sprintf(
            'dashboard:teacher:%d:exam-averages:search:%s:page:%d:per:%d',
            $teacher->id,
            md5(mb_strtolower($search ?? '')),
            $page,
            $perPage,
        );

        $cached = Cache::tags(['dashboard'])->remember($key, now()->addMinutes(10), function () use ($teacher, $search, $page, $perPage) {
            $averages = $this->dashboardRepository->examAverages($teacher->id, $search, $page, $perPage);

            return ['items' => $averages->items(), 'total' => $averages->total()];
        });

        return new LengthAwarePaginator($cached['items'], $cached['total'], $perPage, $page);
    }
}
