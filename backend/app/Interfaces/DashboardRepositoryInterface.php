<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardRepositoryInterface
{
    /**
     * @return array{total_attempts: int, total_students: int, average_percentage: float|string|null}
     */
    public function totals(int $teacherId): array;

    public function bestAttempt(int $teacherId): ?array;

    public function worstAttempt(int $teacherId): ?array;

    public function topStudent(int $teacherId): ?array;

    public function examAverages(int $teacherId, ?string $search, int $page, int $perPage): LengthAwarePaginator;

    public function examsRanking(int $teacherId, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @return array{answered_count: int, pending_count: int, average_percentage: float|string|null, best_percentage: float|string|null}
     */
    public function studentSummary(int $studentId): array;
}
