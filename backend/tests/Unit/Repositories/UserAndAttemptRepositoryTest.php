<?php

namespace Tests\Unit\Repositories;

use App\Enums\UserRole;
use App\Http\Repositories\AttemptRepository;
use App\Http\Repositories\UserRepository;
use Tests\Concerns\CapturesQueries;
use Tests\TestCase;

class UserAndAttemptRepositoryTest extends TestCase
{
    use CapturesQueries;

    public function test_users_by_role_are_filtered_and_ordered_by_name(): void
    {
        $queries = $this->captureQueries(fn () => (new UserRepository)->getByRole(UserRole::Student));

        $this->assertSame("select * from users where role = 'student' order by name asc", $this->sql($queries));
        $this->assertSame(['student'], $queries[0]['bindings']);
    }

    public function test_find_by_role_checks_id_and_role(): void
    {
        $queries = $this->captureQueries(fn () => (new UserRepository)->findByRole(3, UserRole::Teacher));

        $this->assertStringContainsString("where role = 'teacher' and users.id = 3", $this->sql($queries));
        $this->assertSame(['teacher', 3], $queries[0]['bindings']);
    }

    public function test_attempt_existence_is_checked_per_exam_and_student(): void
    {
        $exists = null;
        $queries = $this->captureQueries(function () use (&$exists) {
            $exists = (new AttemptRepository)->existsFor(10, 3);
        });

        $this->assertFalse($exists);
        $this->assertStringContainsString('select exists(select * from attempts where exam_id = 10 and student_id = 3)', $this->sql($queries));
        $this->assertSame([10, 3], $queries[0]['bindings']);
    }

    public function test_review_looks_for_the_student_attempt(): void
    {
        $attempt = 'not called';
        $queries = $this->captureQueries(function () use (&$attempt) {
            $attempt = (new AttemptRepository)->findForStudentWithReview(10, 3);
        });

        $this->assertNull($attempt);
        $this->assertStringContainsString('from attempts where exam_id = 10 and student_id = 3 limit 1', $this->sql($queries));
        $this->assertSame([10, 3], $queries[0]['bindings']);
    }
}
