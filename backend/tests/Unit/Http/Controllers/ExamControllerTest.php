<?php

namespace Tests\Unit\Http\Controllers;

use App\Enums\ExamStatus;
use App\Http\Controllers\ExamController;
use App\Http\Requests\ListExamsRequest;
use App\Http\Requests\StoreExamRequest;
use App\Http\Requests\UpdateExamRequest;
use App\Http\Services\Exam\CreateExamService;
use App\Http\Services\Exam\DeleteExamService;
use App\Http\Services\Exam\GetTeacherExamService;
use App\Http\Services\Exam\ListTeacherExamsService;
use App\Http\Services\Exam\PublishExamService;
use App\Http\Services\Exam\UpdateExamService;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\BuildsExams;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class ExamControllerTest extends TestCase
{
    use BuildsExams, BuildsFormRequests;

    private User $teacher;

    private array $payload = [
        'title' => 'Prova de Geografia',
        'description' => 'Capitais',
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

        config(['cache.default' => 'array']);
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
        $this->teacher = User::factory()->teacher()->make(['id' => 1]);
    }

    public function test_index_returns_paginated_exams(): void
    {
        $exam = $this->examWithQuestions();
        $exam->setAttribute('questions_count', 2);

        $repository = $this->repository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('paginateByTeacher')->once()->with($this->teacher, ExamStatus::Draft, 5)
                ->andReturn(new LengthAwarePaginator([$exam], 1, 5, 1));
        });
        $request = $this->validatedRequest(ListExamsRequest::class, ['status' => 'draft', 'per_page' => 5], 'GET');

        $response = (new ExamController)->index($request, $this->teacher, new ListTeacherExamsService($repository));

        $data = $response->getData(true);
        $this->assertSame('Provas listadas com sucesso.', $data['message']);
        $this->assertSame(10, $data['data']['items'][0]['id']);
        $this->assertSame(['current_page' => 1, 'last_page' => 1, 'per_page' => 5, 'total' => 1], $data['data']['meta']);
    }

    public function test_store_creates_exam_with_201(): void
    {
        $exam = $this->examWithQuestions();

        $repository = $this->repository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('create')->once()->andReturn($exam);
            $mock->shouldReceive('createQuestions')->once()->with($exam, $this->payload['questions']);
            $mock->shouldReceive('loadDetails')->once()->andReturn($exam);
        });
        $request = $this->validatedRequest(StoreExamRequest::class, $this->payload);

        $response = (new ExamController)->store($request, $this->teacher, new CreateExamService($repository));

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Prova criada com sucesso.', $response->getData(true)['message']);
        $this->assertSame('Prova de Geografia', $response->getData(true)['data']['title']);
    }

    public function test_show_returns_exam_with_answer_key(): void
    {
        $exam = $this->examWithQuestions();

        $repository = $this->repository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findForTeacher')->once()->with($this->teacher, 10)->andReturn($exam);
            $mock->shouldReceive('loadDetails')->once()->andReturn($exam);
        });

        $response = (new ExamController)->show($this->teacher, 10, new GetTeacherExamService($repository));

        $this->assertSame('Prova encontrada.', $response->getData(true)['message']);
        $this->assertTrue($response->getData(true)['data']['questions'][0]['options'][1]['is_correct']);
    }

    public function test_update_replaces_draft_content(): void
    {
        $exam = $this->examWithQuestions();

        $repository = $this->repository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($exam);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($exam);
            $mock->shouldReceive('update')->once()->andReturn($exam);
            $mock->shouldReceive('deleteQuestions')->once();
            $mock->shouldReceive('createQuestions')->once();
            $mock->shouldReceive('loadDetails')->once()->andReturn($exam);
        });
        $request = $this->validatedRequest(UpdateExamRequest::class, $this->payload, 'PUT');

        $response = (new ExamController)->update($request, $this->teacher, 10, new UpdateExamService($repository));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Prova atualizada com sucesso.', $response->getData(true)['message']);
    }

    public function test_publish_returns_published_exam(): void
    {
        $draft = $this->examWithQuestions();
        $published = $this->examWithQuestions(['status' => ExamStatus::Published]);

        $repository = $this->repository(function (MockInterface $mock) use ($draft, $published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($draft);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($draft);
            $mock->shouldReceive('publish')->once()->andReturn($published);
            $mock->shouldReceive('loadDetails')->once()->andReturn($published);
        });

        $response = (new ExamController)->publish($this->teacher, 10, new PublishExamService($repository));

        $this->assertSame('Prova publicada com sucesso.', $response->getData(true)['message']);
        $this->assertSame('published', $response->getData(true)['data']['status']);
    }

    public function test_destroy_returns_no_content(): void
    {
        $exam = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);

        $repository = $this->repository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($exam);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($exam);
            $mock->shouldReceive('hasAttempts')->once()->andReturn(false);
            $mock->shouldReceive('delete')->once()->with($exam);
        });

        $response = (new ExamController)->destroy($this->teacher, 10, new DeleteExamService($repository));

        $this->assertSame(204, $response->getStatusCode());
    }

    private function repository(callable $expectations): ExamRepositoryInterface
    {
        return Mockery::mock(ExamRepositoryInterface::class, $expectations);
    }
}
