<?php

namespace Tests\Unit\Services\Exam;

use App\Enums\StudentExamStatus;
use App\Http\Services\Exam\ListAvailableExamsService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ListAvailableExamsServiceTest extends TestCase
{
    public function test_forwards_student_filters_and_page_size_to_repository(): void
    {
        $student = User::factory()->student()->make(['id' => 3]);
        $paginator = new LengthAwarePaginator([], 0, 15);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($student, $paginator) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()
                ->with($student, StudentExamStatus::Pending, 'geo', 15)->andReturn($paginator);
        });

        $this->assertSame($paginator, (new ListAvailableExamsService($repository))->handle($student, StudentExamStatus::Pending, 'geo', 15));
    }
}
