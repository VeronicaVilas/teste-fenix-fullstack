<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\AttemptController;
use App\Http\Controllers\StudentExamController;
use App\Http\Requests\PaginationRequest;
use App\Http\Requests\SubmitAttemptRequest;
use App\Http\Services\Attempt\GetAttemptResultService;
use App\Http\Services\Attempt\SubmitAttemptService;
use App\Http\Services\Exam\GetAvailableExamService;
use App\Http\Services\Exam\ListAvailableExamsService;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Attempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\Concerns\BuildsExams;
use Tests\Concerns\BuildsFormRequests;
use Tests\TestCase;

class StudentFlowControllersTest extends TestCase
{
    use BuildsExams, BuildsFormRequests;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
        $this->student = User::factory()->student()->make(['id' => 3]);
    }

    public function test_index_lists_available_exams(): void
    {
        $exam = $this->examWithQuestions(['status' => 'published']);
        $exam->setRelation('attempts', new Collection);

        $exams = $this->examRepository(function (MockInterface $mock) use ($exam) {
            $mock->shouldReceive('paginatePublishedForStudent')->once()->with($this->student, 10)
                ->andReturn(new LengthAwarePaginator([$exam], 1, 10, 1));
        });

        $response = (new StudentExamController)->index(
            $this->validatedRequest(PaginationRequest::class, [], 'GET'),
            $this->student,
            new ListAvailableExamsService($exams),
        );

        $data = $response->getData(true);
        $this->assertSame('Provas disponíveis listadas com sucesso.', $data['message']);
        $this->assertFalse($data['data']['items'][0]['answered']);
    }

    public function test_show_returns_exam_without_answer_key(): void
    {
        $exam = $this->examWithQuestions(['status' => 'published']);

        $service = new GetAvailableExamService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->with(10)->andReturn($exam)),
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldReceive('existsFor')->once()->with(10, 3)->andReturn(false)),
        );

        $response = (new StudentExamController)->show($this->student, 10, $service);

        $this->assertSame('Prova encontrada.', $response->getData(true)['message']);
        $this->assertStringNotContainsString('is_correct', $response->getContent());
    }

    public function test_store_grades_the_attempt_with_201(): void
    {
        $exam = $this->examWithQuestions(['status' => 'published']);
        $answers = [['question_id' => 1, 'option_id' => 12], ['question_id' => 2, 'option_id' => 22]];

        $service = new SubmitAttemptService(
            $this->examRepository(fn (MockInterface $mock) => $mock->shouldReceive('findPublishedWithQuestions')->once()->andReturn($exam)),
            $this->attemptRepository(function (MockInterface $mock) {
                $mock->shouldReceive('existsFor')->once()->andReturn(false);
                $mock->shouldReceive('create')->once()->andReturnUsing(fn (array $data) => (new Attempt($data))->forceFill(['id' => 7]));
                $mock->shouldReceive('createAnswers')->once();
            }),
        );

        $response = (new AttemptController)->store(
            $this->validatedRequest(SubmitAttemptRequest::class, ['answers' => $answers]),
            $this->student,
            10,
            $service,
        );

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Prova enviada e corrigida com sucesso.', $response->getData(true)['message']);
        $this->assertSame('50.00', $response->getData(true)['data']['percentage']);
    }

    public function test_show_returns_attempt_review(): void
    {
        $service = new GetAttemptResultService(
            $this->attemptRepository(fn (MockInterface $mock) => $mock->shouldReceive('findForStudentWithReview')->once()->with(10, 3)->andReturn($this->reviewedAttempt())),
        );

        $response = (new AttemptController)->show($this->student, 10, $service);

        $this->assertSame('Resultado encontrado.', $response->getData(true)['message']);
        $this->assertSame(12, $response->getData(true)['data']['questions'][0]['correct_option_id']);
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
