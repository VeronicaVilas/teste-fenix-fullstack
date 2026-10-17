<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property array{exam_id: ?int, totals: array, best: ?array, worst: ?array, top_student: ?array, exams: array} $resource
 */
class DashboardSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $summary = $this->resource;

        return [
            'exam_id' => $summary['exam_id'],
            'total_attempts' => $summary['totals']['total_attempts'],
            'total_students' => $summary['totals']['total_students'],
            'average_percentage' => self::percentage($summary['totals']['average_percentage']),
            'best' => self::attempt($summary['best']),
            'worst' => self::attempt($summary['worst']),
            'top_student' => self::topStudent($summary['top_student']),
            'exams' => array_map(fn (array $exam) => [
                'id' => (int) $exam['id'],
                'title' => $exam['title'],
                'attempts_count' => (int) $exam['attempts_count'],
                'average_percentage' => self::percentage($exam['average_percentage']),
                'best_percentage' => self::percentage($exam['best_percentage']),
                'worst_percentage' => self::percentage($exam['worst_percentage']),
            ], $summary['exams']),
        ];
    }

    private static function attempt(?array $attempt): ?array
    {
        if ($attempt === null) {
            return null;
        }

        return [
            'student_name' => $attempt['student_name'],
            'exam_title' => $attempt['exam_title'],
            'score' => (int) $attempt['score'],
            'total_questions' => (int) $attempt['total_questions'],
            'percentage' => self::percentage($attempt['percentage']),
            'submitted_at' => Carbon::parse($attempt['submitted_at'])->toIso8601String(),
        ];
    }

    private static function topStudent(?array $student): ?array
    {
        if ($student === null) {
            return null;
        }

        return [
            'student_name' => $student['student_name'],
            'exams_count' => (int) $student['exams_count'],
            'total_grade' => self::percentage($student['total_grade']),
        ];
    }

    private static function percentage(float|int|string|null $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
