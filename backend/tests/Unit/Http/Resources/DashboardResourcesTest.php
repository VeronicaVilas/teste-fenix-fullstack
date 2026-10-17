<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\DashboardSummaryResource;
use App\Http\Resources\ExamRankingItemResource;
use Tests\Concerns\ResolvesResources;
use Tests\TestCase;

class DashboardResourcesTest extends TestCase
{
    use ResolvesResources;

    public function test_summary_formats_metrics_and_attempts(): void
    {
        $data = $this->resolveResource(new DashboardSummaryResource([
            'totals' => ['total_attempts' => 3, 'total_students' => 3, 'average_percentage' => '66.666'],
            'best' => $this->attempt('Ana Souza', 3, '100'),
            'worst' => $this->attempt('Bruno Lima', 1, 33.333),
            'top_student' => ['student_name' => 'Ana Souza', 'exams_count' => '2', 'total_grade' => 18.5],
            'exams' => [
                ['id' => '10', 'title' => 'Prova de Geografia', 'attempts_count' => '3', 'average_percentage' => 66.67, 'best_percentage' => '100.00', 'worst_percentage' => '33.33'],
            ],
        ]));

        $this->assertSame([
            'total_attempts' => 3,
            'total_students' => 3,
            'average_percentage' => '66.67',
            'best' => [
                'student_name' => 'Ana Souza',
                'exam_title' => 'Prova de Geografia',
                'score' => 3,
                'total_questions' => 3,
                'percentage' => '100.00',
                'submitted_at' => '2026-10-16T10:00:00+00:00',
            ],
            'worst' => [
                'student_name' => 'Bruno Lima',
                'exam_title' => 'Prova de Geografia',
                'score' => 1,
                'total_questions' => 3,
                'percentage' => '33.33',
                'submitted_at' => '2026-10-16T10:00:00+00:00',
            ],
            'top_student' => ['student_name' => 'Ana Souza', 'exams_count' => 2, 'total_grade' => '18.50'],
            'exams' => [
                ['id' => 10, 'title' => 'Prova de Geografia', 'attempts_count' => 3, 'average_percentage' => '66.67', 'best_percentage' => '100.00', 'worst_percentage' => '33.33'],
            ],
        ], $data);
    }

    public function test_summary_without_attempts(): void
    {
        $data = $this->resolveResource(new DashboardSummaryResource([
            'totals' => ['total_attempts' => 0, 'total_students' => 0, 'average_percentage' => null],
            'best' => null,
            'worst' => null,
            'top_student' => null,
            'exams' => [
                ['id' => 11, 'title' => 'Prova de História', 'attempts_count' => 0, 'average_percentage' => null, 'best_percentage' => null, 'worst_percentage' => null],
            ],
        ]));

        $this->assertNull($data['average_percentage']);
        $this->assertNull($data['best']);
        $this->assertNull($data['worst']);
        $this->assertNull($data['top_student']);
        $this->assertNull($data['exams'][0]['average_percentage']);
    }

    private function attempt(string $student, int $score, float|string $percentage): array
    {
        return [
            'student_name' => $student,
            'exam_title' => 'Prova de Geografia',
            'score' => $score,
            'total_questions' => 3,
            'percentage' => $percentage,
            'submitted_at' => '2026-10-16 10:00:00',
        ];
    }

    public function test_exam_ranking_item_casts_database_values(): void
    {
        $this->assertSame([
            'position' => 1,
            'exam_id' => 12,
            'title' => 'Prova de Química',
            'students_count' => 4,
            'average_percentage' => '83.33',
            'best_student_name' => 'Ana Souza',
        ], $this->resolveResource(new ExamRankingItemResource([
            'position' => '1',
            'exam_id' => '12',
            'title' => 'Prova de Química',
            'students_count' => '4',
            'average_percentage' => '83.3333',
            'best_student_name' => 'Ana Souza',
        ])));
    }
}
