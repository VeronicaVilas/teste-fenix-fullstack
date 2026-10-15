<?php

namespace Tests\Unit\Services\Exam;

use App\Http\Services\Exam\GetTeacherExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetTeacherExamServiceTest extends TestCase
{
    public function test_finds_teacher_exam_and_loads_details(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);
        $exam = Exam::factory()->make(['id' => 10, 'teacher_id' => 1]);
        $detailed = Exam::factory()->make(['id' => 10, 'teacher_id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($teacher, $exam, $detailed) {
            $mock->shouldReceive('findForTeacher')->once()->with($teacher, 10)->andReturn($exam);
            $mock->shouldReceive('loadDetails')->once()->with($exam)->andReturn($detailed);
        });

        $this->assertSame($detailed, (new GetTeacherExamService($repository))->handle($teacher, 10));
    }

    public function test_propagates_not_found_without_loading_details(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
            $mock->shouldNotReceive('loadDetails');
        });

        $this->expectException(ModelNotFoundException::class);

        (new GetTeacherExamService($repository))->handle($teacher, 99);
    }
}
