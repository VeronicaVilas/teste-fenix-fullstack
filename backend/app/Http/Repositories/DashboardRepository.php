<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatus;
use App\Interfaces\DashboardRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class DashboardRepository implements DashboardRepositoryInterface
{
    private const ATTEMPT_COLUMNS = [
        'users.name as student_name',
        'exams.title as exam_title',
        'attempts.score',
        'attempts.total_questions',
        'attempts.percentage',
        'attempts.submitted_at',
    ];

    public function totals(int $teacherId, ?int $examId): array
    {
        $totals = $this->attemptsOf($teacherId, $examId)
            ->selectRaw('COUNT(*) as total_attempts')
            ->selectRaw('COUNT(DISTINCT attempts.student_id) as total_students')
            ->selectRaw('ROUND(AVG(attempts.percentage), 2) as average_percentage')
            ->first();

        return [
            'total_attempts' => (int) $totals->total_attempts,
            'total_students' => (int) $totals->total_students,
            'average_percentage' => $totals->average_percentage,
        ];
    }

    public function bestAttempt(int $teacherId, ?int $examId): ?array
    {
        $attempt = $this->attemptsOf($teacherId, $examId)
            ->orderByDesc('attempts.percentage')
            ->orderBy('attempts.submitted_at')
            ->first(self::ATTEMPT_COLUMNS);

        return $attempt ? (array) $attempt : null;
    }

    public function worstAttempt(int $teacherId, ?int $examId): ?array
    {
        $attempt = $this->attemptsOf($teacherId, $examId)
            ->orderBy('attempts.percentage')
            ->orderBy('attempts.submitted_at')
            ->first(self::ATTEMPT_COLUMNS);

        return $attempt ? (array) $attempt : null;
    }

    public function examStats(int $teacherId): array
    {
        return DB::table('exams')
            ->leftJoin('attempts', 'attempts.exam_id', '=', 'exams.id')
            ->where('exams.teacher_id', $teacherId)
            ->where('exams.status', ExamStatus::Published->value)
            ->groupBy('exams.id', 'exams.title', 'exams.published_at')
            ->orderByDesc('exams.published_at')
            ->select('exams.id', 'exams.title')
            ->selectRaw('COUNT(attempts.id) as attempts_count')
            ->selectRaw('ROUND(AVG(attempts.percentage), 2) as average_percentage')
            ->selectRaw('MAX(attempts.percentage) as best_percentage')
            ->selectRaw('MIN(attempts.percentage) as worst_percentage')
            ->get()
            ->map(fn (object $row) => (array) $row)
            ->all();
    }

    public function ranking(int $teacherId, ?int $examId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->attemptsOf($teacherId, $examId)
            ->select(['attempts.id as attempt_id', ...self::ATTEMPT_COLUMNS])
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY attempts.percentage DESC, attempts.submitted_at ASC, attempts.id ASC) as position')
            ->orderByDesc('attempts.percentage')
            ->orderBy('attempts.submitted_at')
            ->orderBy('attempts.id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (object $row) => (array) $row);
    }

    private function attemptsOf(int $teacherId, ?int $examId): Builder
    {
        return DB::table('attempts')
            ->join('exams', 'exams.id', '=', 'attempts.exam_id')
            ->join('users', 'users.id', '=', 'attempts.student_id')
            ->where('exams.teacher_id', $teacherId)
            ->when($examId, fn (Builder $query) => $query->where('attempts.exam_id', $examId));
    }
}
