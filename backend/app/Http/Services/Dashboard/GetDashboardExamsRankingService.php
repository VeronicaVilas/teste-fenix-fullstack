<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final readonly class GetDashboardExamsRankingService
{
    public function __construct(private DashboardRepositoryInterface $dashboardRepository) {}

    public function handle(User $teacher, int $page, int $perPage): LengthAwarePaginatorContract
    {
        $key = sprintf('dashboard:teacher:%d:exams-ranking:page:%d:per:%d', $teacher->id, $page, $perPage);

        $cached = Cache::tags(['dashboard'])->remember($key, now()->addMinutes(10), function () use ($teacher, $page, $perPage) {
            $ranking = $this->dashboardRepository->examsRanking($teacher->id, $page, $perPage);

            return ['items' => $ranking->items(), 'total' => $ranking->total()];
        });

        return new LengthAwarePaginator($cached['items'], $cached['total'], $perPage, $page);
    }
}
