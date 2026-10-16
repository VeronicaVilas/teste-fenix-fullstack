<?php

namespace Tests\Unit\Services\Attempt;

use App\Exceptions\AttemptNotFoundException;
use App\Http\Services\Attempt\GetAttemptResultService;
use App\Interfaces\AttemptRepositoryInterface;
use App\Models\Attempt;
use App\Models\User;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetAttemptResultServiceTest extends TestCase
{
    public function test_returns_the_student_attempt(): void
    {
        $student = User::factory()->student()->make(['id' => 3]);
        $attempt = new Attempt(['exam_id' => 10, 'student_id' => 3]);

        $repository = Mockery::mock(AttemptRepositoryInterface::class, function (MockInterface $mock) use ($attempt) {
            $mock->shouldReceive('findForStudentWithReview')->once()->with(10, 3)->andReturn($attempt);
        });

        $this->assertSame($attempt, (new GetAttemptResultService($repository))->handle($student, 10));
    }

    public function test_throws_when_student_has_not_answered(): void
    {
        $student = User::factory()->student()->make(['id' => 3]);

        $repository = Mockery::mock(AttemptRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForStudentWithReview')->once()->andReturn(null);
        });

        $this->expectException(AttemptNotFoundException::class);
        $this->expectExceptionMessage('Você ainda não respondeu esta prova.');

        (new GetAttemptResultService($repository))->handle($student, 10);
    }
}
