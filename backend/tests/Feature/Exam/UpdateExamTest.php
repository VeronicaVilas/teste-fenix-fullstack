<?php

namespace Tests\Feature\Exam;

use App\Enums\UserRole;
use App\Interfaces\ExamRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class UpdateExamTest extends TestCase
{
    private const URL = '/api/v1/teachers/1/exams/10';

    private array $payload = [
        'title' => 'Prova revisada',
        'description' => 'Nova descrição',
        'questions' => [
            [
                'statement' => 'Qual é o maior planeta?',
                'options' => [
                    ['text' => 'Júpiter', 'is_correct' => true],
                    ['text' => 'Marte', 'is_correct' => false],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());

        $this->mock(UserRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findByRole')->with(1, UserRole::Teacher)
                ->andReturn(User::factory()->teacher()->make(['id' => 1]));
        });
    }

    public function test_updates_draft_and_returns_new_content(): void
    {
        $draft = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($draft) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($draft);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($draft);
            $mock->shouldReceive('update')->once()
                ->with($draft, ['title' => 'Prova revisada', 'description' => 'Nova descrição'])
                ->andReturn($draft);
            $mock->shouldReceive('deleteQuestions')->once()->with($draft);
            $mock->shouldReceive('createQuestions')->once()->with($draft, $this->payload['questions']);
            $mock->shouldReceive('loadDetails')->once()->andReturn($this->updatedExam());
        });

        $this->putJson(self::URL, $this->payload)
            ->assertOk()
            ->assertJsonPath('message', 'Prova atualizada com sucesso.')
            ->assertJsonPath('data.title', 'Prova revisada')
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.questions.0.statement', 'Qual é o maior planeta?')
            ->assertJsonPath('data.questions.0.options.0.is_correct', true);
    }

    public function test_returns_409_for_published_exam(): void
    {
        $published = Exam::factory()->published()->make(['id' => 10, 'teacher_id' => 1]);

        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) use ($published) {
            $mock->shouldReceive('findForTeacher')->once()->andReturn($published);
            $mock->shouldReceive('lockForUpdate')->once()->andReturn($published);
            $mock->shouldNotReceive('update');
            $mock->shouldNotReceive('deleteQuestions');
        });

        $this->putJson(self::URL, $this->payload)
            ->assertConflict()
            ->assertExactJson([
                'message' => 'Prova publicada não pode ser alterada.',
                'status' => 409,
                'errors' => [],
                'data' => [],
            ]);
    }

    public function test_validates_like_creation(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('findForTeacher');
        });

        $payload = $this->payload;
        $payload['questions'][0]['options'][1]['is_correct'] = true;

        $this->putJson(self::URL, $payload)
            ->assertUnprocessable()
            ->assertJsonPath('errors', [
                'questions.0.options' => ['Cada questão deve ter exatamente uma alternativa correta.'],
            ]);
    }

    public function test_returns_404_for_exam_of_another_teacher(): void
    {
        $this->mock(ExamRepositoryInterface::class, function (MockInterface $mock) {
            $mock->shouldReceive('findForTeacher')->once()->andThrow(new ModelNotFoundException);
            $mock->shouldNotReceive('update');
        });

        $this->putJson(self::URL, $this->payload)->assertNotFound();
    }

    private function updatedExam(): Exam
    {
        $question = Question::factory()->make(['id' => 30, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é o maior planeta?']);
        $question->setRelation('options', new Collection([
            Option::factory()->correct()->make(['id' => 41, 'question_id' => 30, 'position' => 1, 'text' => 'Júpiter']),
            Option::factory()->make(['id' => 42, 'question_id' => 30, 'position' => 2, 'text' => 'Marte']),
        ]));

        $exam = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1, 'title' => 'Prova revisada', 'description' => 'Nova descrição']);
        $exam->setRelation('questions', new Collection([$question]));
        $exam->setAttribute('questions_count', 1);
        $exam->setAttribute('attempts_count', 0);

        return $exam;
    }
}
