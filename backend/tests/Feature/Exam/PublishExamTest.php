<?php

namespace Tests\Feature\Exam;

use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class PublishExamTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/exams/10/publish';

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

    public function test_publishes_draft_and_clears_dashboard_cache(): void
    {
        Cache::tags(['dashboard'])->put('dashboard:teacher:1:summary:exam:all', 'cached summary');

        $draft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $published = Exam::factory()->published()->make([
            'id' => 10,
            'teacher_id' => 1,
            'published_at' => Carbon::parse('2026-10-15 14:00:00'),
        ]);
        $published->setRelation('questions', new Collection);
        $published->setAttribute('questions_count', 0);
        $published->setAttribute('attempts_count', 0);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($draft, $published) {
            $mock->shouldReceive('findForTeacher')->once()
                ->withArgs(fn (User $teacher, int $examId) => $teacher->id === 1 && $examId === 10)
                ->andReturn($draft);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($draft);
            $mock->shouldReceive('publish')->once()->with($draft)->andReturn($published);
            $mock->shouldReceive('loadDetails')->once()->andReturn($published);
        });

        $this->patchJson(self::URL)
            ->assertOk()
            ->assertJsonPath('message', 'Prova publicada com sucesso.')
            ->assertJsonPath('data.status', 'published')
            ->assertJsonPath('data.published_at', '2026-10-15T14:00:00+00:00');

        $this->assertNull(Cache::tags(['dashboard'])->get('dashboard:teacher:1:summary:exam:all'));
    }

    public function test_returns_409_when_already_published(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldNotReceive('publish');
        });

        $this->patchJson(self::URL)
            ->assertConflict()
            ->assertJsonPath('message', 'Prova publicada não pode ser alterada.');
    }

    public function test_returns_404_for_exam_of_another_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
            $mock->shouldNotReceive('publish');
        });

        $this->patchJson(self::URL)->assertNotFound();
    }
}
