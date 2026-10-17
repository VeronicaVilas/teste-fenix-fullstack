<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final readonly class GetDashboardSummaryService
{
    public function __construct(
        private DashboardRepositoryInterface $dashboardRepository,
        private ExamRepositoryInterface $examRepository,
    ) {}

    public function handle(User $teacher, ?int $examId): array
    {
        if ($examId !== null) {
            $this->examRepository->findForTeacher($teacher, $examId);
        }

        $key = sprintf('dashboard:teacher:%d:summary:exam:%s', $teacher->id, $examId ?? 'all');

        return Cache::tags(['dashboard'])->remember($key, now()->addMinutes(10), fn () => [
            'exam_id' => $examId,
            'totals' => $this->dashboardRepository->totals($teacher->id, $examId),
            'best' => $this->dashboardRepository->bestAttempt($teacher->id, $examId),
            'worst' => $this->dashboardRepository->worstAttempt($teacher->id, $examId),
            'top_student' => $this->dashboardRepository->topStudent($teacher->id, $examId),
            'exams' => $this->dashboardRepository->examStats($teacher->id),
        ]);
    }
}
