<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final readonly class GetDashboardSummaryService
{
    public function __construct(private DashboardRepositoryInterface $dashboardRepository) {}

    public function handle(User $teacher): array
    {
        $key = sprintf('dashboard:teacher:%d:summary', $teacher->id);

        return Cache::tags(['dashboard'])->remember($key, now()->addMinutes(10), fn () => [
            'totals' => $this->dashboardRepository->totals($teacher->id),
            'best' => $this->dashboardRepository->bestAttempt($teacher->id),
            'worst' => $this->dashboardRepository->worstAttempt($teacher->id),
            'top_student' => $this->dashboardRepository->topStudent($teacher->id),
            'exams' => $this->dashboardRepository->examStats($teacher->id),
        ]);
    }
}
