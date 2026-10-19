<?php

namespace Tests\Unit\Repositories;

use App\Http\Repositories\DashboardRepository;
use Tests\Concerns\CapturesQueries;
use Tests\TestCase;

class DashboardRepositoryTest extends TestCase
{
    use CapturesQueries;

    private DashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new DashboardRepository;
    }

    public function test_best_and_worst_attempts_are_ordered_by_percentage_then_submission(): void
    {
        $best = $this->captureQueries(fn () => $this->repository->bestAttempt(1));
        $worst = $this->captureQueries(fn () => $this->repository->worstAttempt(1));

        $this->assertStringContainsString('where exams.teacher_id = 1 order by attempts.percentage desc, attempts.submitted_at asc limit 1', $this->sql($best));
        $this->assertSame([1], $best[0]['bindings']);
        $this->assertStringContainsString('where exams.teacher_id = 1 order by attempts.percentage asc, attempts.submitted_at asc limit 1', $this->sql($worst));
        $this->assertSame([1], $worst[0]['bindings']);
    }

    public function test_top_student_sums_grades_per_student(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->topStudent(1));
        $sql = $this->sql($queries);

        $this->assertStringContainsString('round(sum(attempts.percentage) / 10, 2) as total_grade', $sql);
        $this->assertStringContainsString('group by users.id, users.name order by total_grade desc, users.name asc limit 1', $sql);
    }

    public function test_exam_stats_include_only_published_exams_of_the_teacher(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->examStats(1));
        $sql = $this->sql($queries);

        $this->assertStringContainsString('from exams left join attempts on attempts.exam_id = exams.id', $sql);
        $this->assertStringContainsString("where exams.teacher_id = 1 and exams.status = 'published'", $sql);
        $this->assertSame([1, 'published'], $queries[0]['bindings']);
    }

    public function test_student_summary_counts_pending_exams_and_aggregates_attempts(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->studentSummary(3));

        $this->assertStringContainsString("select count(*) as aggregate from exams where exams.status = 'published' and not exists (select 1 from attempts where attempts.exam_id = exams.id and attempts.student_id = 3)", $this->sql($queries, 0));
        $this->assertSame(['published', 3], $queries[0]['bindings']);
        $this->assertStringContainsString('select count(*) as answered_count, round(avg(percentage), 2) as average_percentage, max(percentage) as best_percentage from attempts where student_id = 3', $this->sql($queries, 1));
        $this->assertSame([3], $queries[1]['bindings']);
    }

    public function test_exams_ranking_groups_exams_and_ranks_by_average(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->examsRanking(1, 1, 4));
        $sql = $this->sql($queries);

        $this->assertStringContainsString('row_number() over (order by avg(attempts.percentage) desc, exams.title asc, exams.id asc) as position', $sql);
        $this->assertStringContainsString('as best_student_name', $sql);
        $this->assertStringContainsString('group by exams.id, exams.title', $sql);
        $this->assertSame([1, 'published'], $queries[0]['bindings']);
    }
}
