<?php

namespace App\Interfaces;

interface DashboardRepositoryInterface
{
    /**
     * @return array{total_attempts: int, total_students: int, average_percentage: float|string|null}
     */
    public function totals(int $teacherId, ?int $examId): array;

    public function bestAttempt(int $teacherId, ?int $examId): ?array;

    public function worstAttempt(int $teacherId, ?int $examId): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function examStats(int $teacherId): array;
}
