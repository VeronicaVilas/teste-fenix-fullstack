<?php

namespace Tests\Feature\Exam;

use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class DeleteExamTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/exams/10';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_deletes_draft_with_204(): void
    {
        $draft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $this->mockRepository($draft, hasAttempts: false, shouldDelete: true);

        $this->deleteJson(self::URL)
            ->assertNoContent();
    }

    public function test_deletes_published_exam_without_attempts_and_clears_cache(): void
    {
        Cache::put('exam:10:published', 'cached exam');
        Cache::tags(['dashboard'])->put('dashboard:teacher:1:summary:exam:all', 'cached summary');

        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $this->mockRepository($published, hasAttempts: false, shouldDelete: true);

        $this->deleteJson(self::URL)->assertNoContent();

        $this->assertNull(Cache::get('exam:10:published'));
        $this->assertNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary:exam:all'));
    }

    public function test_returns_409_when_exam_has_attempts(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $this->mockRepository($published, hasAttempts: true, shouldDelete: false);

        $this->deleteJson(self::URL)
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Prova já respondida por alunos não pode ser excluída.',
                'status' => 409,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_returns_404_for_exam_of_another_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
            $mock->shouldNotReceive('delete');
        });

        $this->deleteJson(self::URL)->assertNotFound();
    }

    private function mockRepository(Exam $exam, bool $hasAttempts, bool $shouldDelete): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($exam, $hasAttempts, $shouldDelete) {
            $mock->shouldReceive('findForTeacher')->once()
                ->withArgs(fn (User $teacher, int $examId) => $teacher->id === 1 && $examId === 10)
                ->andReturn($exam);
            $mock->shouldReceive('lockForUpdate')->once()->with($exam)->andReturn($exam);
            $mock->shouldReceive('hasAttempts')->once()->with($exam)->andReturn($hasAttempts);

            $shouldDelete
                ? $mock->shouldReceive('delete')->once()->with($exam)
                : $mock->shouldNotReceive('delete');
        });
    }
}
