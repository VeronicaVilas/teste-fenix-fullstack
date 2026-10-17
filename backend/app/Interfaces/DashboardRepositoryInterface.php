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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function examStats(int $teacherId): array;

    public function examsRanking(int $teacherId, int $page, int $perPage): LengthAwarePaginator;
}
