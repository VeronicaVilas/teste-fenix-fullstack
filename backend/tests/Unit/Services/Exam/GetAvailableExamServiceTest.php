<?php

namespace Tests\Unit\Services\Exam;

use App\Enums\ExamStatus;
use App\Http\Services\Exam\GetAvailableExamService;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetAvailableExamServiceTest extends TestCase
{
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        $this->student = User::factory()->student()->make(['id' => 3]);
    }

    public function test_returns_exam_and_answered_flag(): void
    {
        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $service = new GetAvailableExamService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->with(10)->andReturn($exam)),
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldReceive('existsFor')->once()->with(10, 3)->andReturn(true)),
        );

        $this->assertSame(['exam' => $exam, 'answered' => true], $service->handle($this->student, 10));
    }

    public function test_caches_the_published_exam(): void
    {
        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $service = new GetAvailableExamService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->andReturn($exam)),
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldReceive('existsFor')->twice()->andReturn(false)),
        );

        $service->handle($this->student, 10);
        $second = $service->handle($this->student, 10);

        $this->assertSame($exam, $second['exam']);
        $this->assertSame($exam, Cache::get('exam:10:published'));
    }

    public function test_cached_exam_survives_serialization(): void
    {
        config(['cache.stores.array.serialize' => true]);

        $question = Question::factory()->make(['id' => 20, 'exam_id' => 10, 'position' => 1]);
        $question->setRelation('options', new Collection([
            Option::factory()->correct()->make(['id' => 31, 'question_id' => 20, 'position' => 1]),
        ]));
        $exam = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);
        $exam->setRelation('questions', new Collection([$question]));
        $exam->isPublished();

        $service = new GetAvailableExamService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->andReturn($exam)),
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldReceive('existsFor')->twice()->andReturn(false)),
        );

        $service->handle($this->student, 10);
        $cached = $service->handle($this->student, 10)['exam'];

        $this->assertInstanceOf(Exam::class, $cached);
        $this->assertNotSame($exam, $cached);
        $this->assertSame(ExamStatus::Published, $cached->status);
        $this->assertSame(31, $cached->questions->first()->options->first()->id);
    }

    public function test_does_not_cache_missing_exam(): void
    {
        $service = new GetAvailableExamService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->andThrow(new ModelNotFoundException)),
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldNotReceive('existsFor')),
        );

        try {
            $service->handle($this->student, 10);
            $this->fail('Expected ModelNotFoundException.');
        } catch (ModelNotFoundException) {
            $this->assertFalse(Cache::has('exam:10:published'));
        }
    }

    private function examRepository(callable $expectations): ExamRepositoryInterface
    {
        return Mockery::mock(ExamRepositoryInterface::class, $expectations);
    }

    private function attemptRepository(callable $expectations): AttemptRepositoryInterface
    {
        return Mockery::mock(AttemptRepositoryInterface::class, $expectations);
    }
}
