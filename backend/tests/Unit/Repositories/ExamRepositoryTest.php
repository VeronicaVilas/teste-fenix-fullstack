<?php

namespace Tests\Unit\Repositories;

use App\Enums\ExamStatus;
use App\Http\Repositories\ExamRepository;
use App\Models\Exam;
use App\Models\User;
use Tests\Concerns\CapturesQueries;
use Tests\TestCase;

class ExamRepositoryTest extends TestCase
{
    use CapturesQueries;

    private ExamRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ExamRepository;
    }

    public function test_teacher_exams_are_scoped_to_the_teacher_and_status(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);

        $queries = $this->captureQueries(fn () => $this->repository->paginateByTeacher($teacher, ExamStatus::Draft, 10));

        $this->assertStringContainsString("from exams where exams.teacher_id = 1 and exams.teacher_id is not null and status = 'draft'", $this->sql($queries));
        $this->assertSame([1, 'draft'], $queries[0]['bindings']);
    }

    public function test_find_for_teacher_searches_only_the_teacher_exams(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);

        $queries = $this->captureQueries(fn () => $this->repository->findForTeacher($teacher, 10));

        $this->assertStringContainsString('where exams.teacher_id = 1 and exams.teacher_id is not null and exams.id = 10', $this->sql($queries));
        $this->assertSame([1, 10], $queries[0]['bindings']);
    }

    public function test_student_sees_only_published_exams(): void
    {
        $student = User::factory()->student()->make(['id' => 3]);

        $listQueries = $this->captureQueries(fn () => $this->repository->paginatePublishedForStudent($student, 10));
        $findQueries = $this->captureQueries(fn () => $this->repository->findPublishedWithQuestions(10));

        $this->assertStringContainsString("from exams where status = 'published'", $this->sql($listQueries));
        $this->assertSame(['published'], $listQueries[0]['bindings']);
        $this->assertStringContainsString("where status = 'published' and exams.id = 10", $this->sql($findQueries));
        $this->assertSame(['published', 10], $findQueries[0]['bindings']);
    }

    public function test_lock_for_update_locks_the_row(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->lockForUpdate($this->storedExam()));

        $this->assertStringEndsWith('for update', $this->sql($queries));
        $this->assertSame([10], $queries[0]['bindings']);
    }

    public function test_update_publish_and_delete_write_the_exam(): void
    {
        $updateQueries = $this->captureQueries(fn () => $this->repository->update($this->storedExam(), ['title' => 'Nova']));
        $publishQueries = $this->captureQueries(fn () => $this->repository->publish($this->storedExam()));
        $deleteQueries = $this->captureQueries(fn () => $this->repository->delete($this->storedExam()));

        $this->assertStringStartsWith("update exams set title = 'nova'", $this->sql($updateQueries));
        $this->assertStringEndsWith('where id = 10', $this->sql($updateQueries));
        $this->assertStringStartsWith("update exams set status = 'published', published_at =", $this->sql($publishQueries));
        $this->assertSame('delete from exams where id = 10', $this->sql($deleteQueries));
    }

    public function test_questions_are_deleted_and_attempts_checked_by_exam(): void
    {
        $exam = $this->storedExam();
        $hasAttempts = null;

        $deleteQueries = $this->captureQueries(fn () => $this->repository->deleteQuestions($exam));
        $attemptQueries = $this->captureQueries(function () use ($exam, &$hasAttempts) {
            $hasAttempts = $this->repository->hasAttempts($exam);
        });

        $this->assertStringStartsWith('delete from questions where questions.exam_id = 10', $this->sql($deleteQueries));
        $this->assertFalse($hasAttempts);
        $this->assertStringContainsString('from attempts where attempts.exam_id = 10', $this->sql($attemptQueries));
    }

    public function test_details_load_questions_options_and_counts(): void
    {
        $queries = $this->captureQueries(fn () => $this->repository->loadDetails($this->storedExam()));

        $sql = collect($queries)->map(fn (array $query) => strtolower(str_replace('"', '', $query['query'])))->implode(' | ');

        $this->assertStringContainsString('from questions where questions.exam_id in (10)', $sql);
        $this->assertStringContainsString('count(*)', $sql);
    }

    private function storedExam(): Exam
    {
        $exam = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $exam->exists = true;
        $exam->syncOriginal();

        return $exam;
    }
}
