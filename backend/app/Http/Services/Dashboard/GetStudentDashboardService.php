<?php

namespace App\Http\Services\Dashboard;

use App\Interfaces\DashboardRepositoryInterface;
use App\Models\User;

final readonly class GetStudentDashboardService
{
    public function __construct(private DashboardRepositoryInterface $dashboardRepository) {}

    public function handle(User $student): array
    {
        return $this->dashboardRepository->studentSummary($student->id);
    }
}
