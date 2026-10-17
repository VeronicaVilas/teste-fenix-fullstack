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
        $best = $this->captureQueries(fn () => $this->repository->bestAttempt(1, null));
        $worst = $this->captureQueries(fn () => $this->repository->worstAttempt(1, 10));

        $this->assertStringContainsString('where exams.teacher_id = 1 order by attempts.percentage desc, attempts.submitted_at asc limit 1', $this->sql($best));
        $this->assertSame([1], $best[0]['bindings']);
        $this->assertStringContainsString('where exams.teacher_id = 1 and attempts.exam_id = 10 order by attempts.percentage asc, attempts.submitted_at asc limit 1', $this->sql($worst));
        $this->assertSame([1, 10], $worst[0]['bindings']);
    }

    public function test_top_student_sums_grades_per_student(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->topStudent(1, null));
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

    public function test_attempts_ranking_counts_only_the_teacher_attempts(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->ranking(1, 10, 1, 10));

        $this->assertStringContainsString('select count(*) as aggregate from attempts inner join exams', $this->sql($queries));
        $this->assertSame([1, 10], $queries[0]['bindings']);
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
