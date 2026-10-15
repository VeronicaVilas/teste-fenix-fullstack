<?php

namespace Tests\Unit\Services\Exam;

use App\Http\Services\Exam\CreateExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class CreateExamServiceTest extends TestCase
{
    private array $questions = [
        [
            'statement' => 'Qual é a capital do Brasil?',
            'options' => [
                ['text' => 'São Paulo', 'is_correct' => false],
                ['text' => 'Brasília', 'is_correct' => true],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_creates_exam_for_teacher_with_questions_and_returns_details(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 7]);
        $created = Exam::factory()->make(['id' => 10, 'teacher_id' => 7]);
        $detailed = Exam::factory()->make(['id' => 10, 'teacher_id' => 7]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($created, $detailed) {
            $mock->shouldReceive('create')->once()->with([
                'teacher_id' => 7,
                'title' => 'Prova de Geografia',
                'description' => 'Capitais',
            ])->andReturn($created);
            $mock->shouldReceive('createQuestions')->once()->with($created, $this->questions);
            $mock->shouldReceive('loadDetails')->once()->with($created)->andReturn($detailed);
        });

        $result = (new CreateExamService($repository))->handle($teacher, [
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'questions' => $this->questions,
        ]);

        $this->assertSame($detailed, $result);
    }

    public function test_description_is_optional(): void
    {
        $teacher = User::factory()->teacher()->make(['id' => 7]);
        $exam = Exam::factory()->make(['id' => 10, 'teacher_id' => 7]);

        $repository = Mockery::mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('create')->once()->with([
                'teacher_id' => 7,
                'title' => 'Prova sem descrição',
                'description' => null,
            ])->andReturn($exam);
            $mock->shouldReceive('createQuestions')->once();
            $mock->shouldReceive('loadDetails')->once()->andReturn($exam);
        });

        (new CreateExamService($repository))->handle($teacher, [
            'title' => 'Prova sem descrição',
            'questions' => $this->questions,
        ]);
    }
}
