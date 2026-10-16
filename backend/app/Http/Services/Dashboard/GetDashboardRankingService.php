<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

final readonly class GetDashboardRankingService
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
        private ExamRepositoryInterface $examRepository,
    ) {}

    public function handle(User $teacher, ?int $examId, int $page, int $perPage): LengthAwarePaginatorContract
    {
        if ($examId !== null) {
            $this->examRepository->findForTeacher($teacher, $examId);
        }

        $key = sprintf(
            'dashboard:teacher:%d:ranking:exam:%s:page:%d:per:%d',
            $teacher->id,
            $examId ?? 'all',
            $page,
            $perPage,
        );

        $cached = Cache::tags(['dashboard'])->remember($key, now()->addMinutes(10), function () use ($teacher, $examId, $page, $perPage) {
            $ranking = $this->dashboardRepository->ranking($teacher->id, $examId, $page, $perPage);

            return ['items' => $ranking->items(), 'total' => $ranking->total()];
        });

        return new LengthAwarePaginator($cached['items'], $cached['total'], $perPage, $page);
    }
}
