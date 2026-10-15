<?php

namespace Tests\Unit\Services\Exam;

use App\Exceptions\ExamNotEditableException;
use App\Http\Services\Exam\UpdateExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateExamServiceTest extends TestCase
{
    private User $teacher;

    private array $data = [
        'title' => 'Prova revisada',
        'description' => null,
        'questions' => [
            [
                'statement' => 'Qual é a capital do Brasil?',
                'options' => [
                    ['text' => 'São Paulo', 'is_correct' => false],
                    ['text' => 'Brasília', 'is_correct' => true],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_replaces_draft_content_and_returns_details(): void
    {
        $found = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $locked = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $detailed = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($found, $locked, $detailed) {
            $mock->shouldReceive('findForTeacher')->once()->with($this->teacher, 10)->andReturn($found);
            $mock->shouldReceive('lockForUpdate')->once()->with($found)->andReturn($locked);
            $mock->shouldReceive('update')->once()
                ->with($locked, ['title' => 'Prova revisada', 'description' => null])
                ->andReturn($locked);
            $mock->shouldReceive('deleteQuestions')->once()->with($locked)->ordered();
            $mock->shouldReceive('createQuestions')->once()->with($locked, $this->data['questions'])->ordered();
            $mock->shouldReceive('loadDetails')->once()->with($locked)->andReturn($detailed);
        });

        $result = (new UpdateExamService($repository))->handle($this->teacher, 10, $this->data);

        $this->assertSame($detailed, $result);
    }

    public function test_blocks_published_exam_without_changing_it(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldNotReceive('update');
            $mock->shouldNotReceive('deleteQuestions');
            $mock->shouldNotReceive('createQuestions');
        });

        $this->expectException(ExamNotEditableException::class);
        $this->expectExceptionMessage('Prova publicada não pode ser alterada.');

        (new UpdateExamService($repository))->handle($this->teacher, 10, $this->data);
    }

    public function test_checks_status_on_the_locked_record(): void
    {
        $staleDraft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);
        $publishedMeanwhile = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($staleDraft, $publishedMeanwhile) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($staleDraft);
            $mock->shouldReceive('lockForUpdate')->once()->with($staleDraft)->andReturn($publishedMeanwhile);
            $mock->shouldNotReceive('update');
        });

        $this->expectException(ExamNotEditableException::class);

        (new UpdateExamService($repository))->handle($this->teacher, 10, $this->data);
    }
}
