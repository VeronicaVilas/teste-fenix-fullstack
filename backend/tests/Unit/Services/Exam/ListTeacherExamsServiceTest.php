<?php

namespace Tests\Unit\Services\Exam;

use App\Enums\ExamStatus;
use App\Http\Services\Exam\ListTeacherExamsService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ListTeacherExamsServiceTest extends TestCase
{
    public function test_forwards_teacher_status_and_page_size_to_repository(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);
        $paginator = new LengthAwarePaginator([], 0, 25);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($teacher, $paginator) {
            $mock->shouldReceive('paginateByTeacher')->once()
                ->with($teacher, ExamStatus::Published, 25)
                ->andReturn($paginator);
        });

        $result = (new ListTeacherExamsService($repository))->handle($teacher, ExamStatus::Published, 25);

        $this->assertSame($paginator, $result);
    }

    public function test_status_filter_is_optional(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($teacher) {
            $mock->shouldReceive('paginateByTeacher')->once()
                ->with($teacher, null, 10)
                ->andReturn(new LengthAwarePaginator([], 0, 10));
        });

        (new ListTeacherExamsService($repository))->handle($teacher, null, 10);
    }
}
