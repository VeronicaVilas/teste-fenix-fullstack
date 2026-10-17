<?php

namespace App\Interfaces;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DashboardRepositoryInterface
{
    /**
     * @return array{total_attempts: int, total_students: int, average_percentage: float|string|null}
     */
    public function totals(int $teacherId, ?int $examId): array;

    public function bestAttempt(int $teacherId, ?int $examId): ?array;

    public function worstAttempt(int $teacherId, ?int $examId): ?array;

    public function topStudent(int $teacherId, ?int $examId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function examStats(int $teacherId): array;

    public function ranking(int $teacherId, ?int $examId, int $page, int $perPage): LengthAwarePaginator;
}
