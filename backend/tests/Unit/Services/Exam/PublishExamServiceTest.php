<?php

namespace Tests\Unit\Services\Exam;

use App\Exceptions\ExamNotEditableException;
use App\Http\Services\Exam\PublishExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class PublishExamServiceTest extends TestCase
{
    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_publishes_draft_clears_dashboard_cache_and_returns_details(): void
    {
        $draft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $detailed = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        Cache::shouldReceive('tags')->once()->with(['dashboard'])->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($draft, $published, $detailed) {
            $mock->shouldReceive('findForTeacher')->once()->with($this->teacher, 10)->andReturn($draft);
            $mock->shouldReceive('lockForUpdate')->once()->with($draft)->andReturn($draft);
            $mock->shouldReceive('publish')->once()->with($draft)->andReturn($published);
            $mock->shouldReceive('loadDetails')->once()->with($published)->andReturn($detailed);
        });

        $this->assertSame($detailed, (new PublishExamService($repository))->handle($this->teacher, 10));
    }

    public function test_refuses_to_publish_twice(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        Cache::shouldReceive('tags')->never();

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldNotReceive('publish');
        });

        $this->expectException(ExamNotEditableException::class);

        (new PublishExamService($repository))->handle($this->teacher, 10);
    }
}
