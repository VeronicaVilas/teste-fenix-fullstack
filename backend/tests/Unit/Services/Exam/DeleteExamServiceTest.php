<?php

namespace Tests\Unit\Services\Exam;

use App\Exceptions\ExamHasAttemptsException;
use App\Http\Services\Exam\DeleteExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteExamServiceTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_deletes_draft_without_touching_cache(): void
    {
        $draft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        Cache::shouldReceive('forget')->never();
        Cache::shouldReceive('tags')->never();

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($draft) {
            $mock->shouldReceive('findForTeacher')->once()->with($this->teacher, 10)->andReturn($draft);
            $mock->shouldReceive('lockForUpdate')->once()->with($draft)->andReturn($draft);
            $mock->shouldReceive('hasAttempts')->once()->with($draft)->andReturn(false);
            $mock->shouldReceive('delete')->once()->with($draft);
        });

        (new DeleteExamService($repository))->handle($this->teacher, 10);
    }

    public function test_deletes_published_exam_without_attempts_and_clears_cache(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        Cache::shouldReceive('forget')->once()->with('exam:10:published');
        Cache::shouldReceive('tags')->once()->with(['dashboard'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldReceive('hasAttempts')->once()->andReturn(false);
            $mock->shouldReceive('delete')->once()->with($published);
        });

        (new DeleteExamService($repository))->handle($this->teacher, 10);
    }

    public function test_blocks_exam_with_attempts(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        Cache::shouldReceive('forget')->never();

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldReceive('hasAttempts')->once()->andReturn(true);
            $mock->shouldNotReceive('delete');
        });

        $this->expectException(ExamHasAttemptsException::class);
        $this->expectExceptionMessage('Prova já respondida por alunos não pode ser excluída.');

        (new DeleteExamService($repository))->handle($this->teacher, 10);
    }
}
