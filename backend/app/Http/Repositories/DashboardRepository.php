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

    public function totals(int $teacherId): array
    {
        $totals = $this->attemptsOf($teacherId)
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

    public function bestAttempt(int $teacherId): ?array
    {
        $attempt = $this->attemptsOf($teacherId)
            ->orderByDesc('attempts.percentage')
            ->orderBy('attempts.submitted_at')
            ->first(self::ATTEMPT_COLUMNS);

        return $attempt ? (array) $attempt : null;
    }

    public function worstAttempt(int $teacherId): ?array
    {
        $attempt = $this->attemptsOf($teacherId)
            ->orderBy('attempts.percentage')
            ->orderBy('attempts.submitted_at')
            ->first(self::ATTEMPT_COLUMNS);

        return $attempt ? (array) $attempt : null;
    }

    public function topStudent(int $teacherId): ?array
    {
        $student = $this->attemptsOf($teacherId)
            ->groupBy('users.id', 'users.name')
            ->select('users.name as student_name')
            ->selectRaw('COUNT(attempts.id) as exams_count')
            ->selectRaw('ROUND(SUM(attempts.percentage) / 10, 2) as total_grade')
            ->orderByDesc('total_grade')
            ->orderBy('users.name')
            ->first();

        return $student ? (array) $student : null;
    }

    public function examAverages(int $teacherId, ?string $search, int $page, int $perPage): LengthAwarePaginator
    {
        return DB::table('exams')
            ->leftJoin('attempts', 'attempts.exam_id', '=', 'exams.id')
            ->where('exams.teacher_id', $teacherId)
            ->where('exams.status', ExamStatus::Published->value)
            ->when($search, fn (Builder $query) => $query->whereLike('exams.title', '%'.addcslashes($search, '%_\\').'%'))
            ->groupBy('exams.id', 'exams.title', 'exams.published_at')
            ->select('exams.id', 'exams.title')
            ->selectRaw('COUNT(attempts.id) as attempts_count')
            ->selectRaw('ROUND(AVG(attempts.percentage), 2) as average_percentage')
            ->orderByDesc('exams.published_at')
            ->orderByDesc('exams.id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (object $row) => (array) $row);
    }

    public function examsRanking(int $teacherId, int $page, int $perPage): LengthAwarePaginator
    {
        $bestStudent = DB::table('attempts as best')
            ->join('users as best_users', 'best_users.id', '=', 'best.student_id')
            ->whereColumn('best.exam_id', 'exams.id')
            ->orderByDesc('best.percentage')
            ->orderBy('best.submitted_at')
            ->orderBy('best.id')
            ->limit(1)
            ->select('best_users.name');

        return DB::table('exams')
            ->join('attempts', 'attempts.exam_id', '=', 'exams.id')
            ->where('exams.teacher_id', $teacherId)
            ->where('exams.status', ExamStatus::Published->value)
            ->groupBy('exams.id', 'exams.title')
            ->select('exams.id as exam_id', 'exams.title')
            ->selectRaw('COUNT(attempts.id) as students_count')
            ->selectRaw('ROUND(AVG(attempts.percentage), 2) as average_percentage')
            ->selectSub($bestStudent, 'best_student_name')
            ->selectRaw('ROW_NUMBER() OVER (ORDER BY AVG(attempts.percentage) DESC, exams.title ASC, exams.id ASC) as position')
            ->orderByRaw('AVG(attempts.percentage) DESC')
            ->orderBy('exams.title')
            ->orderBy('exams.id')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(fn (object $row) => (array) $row);
    }

    public function studentSummary(int $studentId): array
    {
        $pendingCount = DB::table('exams')
            ->where('exams.status', ExamStatus::Published->value)
            ->whereNotExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('attempts')
                ->whereColumn('attempts.exam_id', 'exams.id')
                ->where('attempts.student_id', $studentId))
            ->count();

        $attempts = DB::table('attempts')
            ->where('student_id', $studentId)
            ->selectRaw('COUNT(*) as answered_count')
            ->selectRaw('ROUND(AVG(percentage), 2) as average_percentage')
            ->selectRaw('MAX(percentage) as best_percentage')
            ->first();

        return [
            'answered_count' => (int) $attempts->answered_count,
            'pending_count' => $pendingCount,
            'average_percentage' => $attempts->average_percentage,
            'best_percentage' => $attempts->best_percentage,
        ];
    }

    private function attemptsOf(int $teacherId): Builder
    {
        return DB::table('attempts')
            ->join('exams', 'exams.id', '=', 'attempts.exam_id')
            ->join('users', 'users.id', '=', 'attempts.student_id')
            ->where('exams.teacher_id', $teacherId);
    }
}
