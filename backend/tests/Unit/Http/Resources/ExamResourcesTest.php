<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamSummaryResource;
use App\Http\Resources\OptionResource;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\UserResource;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsExams;
use Tests\Concerns\ResolvesResources;
use Tests\TestCase;

class ExamResourcesTest extends TestCase
{
    use BuildsExams, ResolvesResources;

    public function test_exam_resource_shows_answer_key_and_counts(): void
    {
        $exam = $this->examWithQuestions([
            'status' => 'published',
            'published_at' => Carbon::parse('2026-10-16 10:00:00'),
        ]);
        $exam->setAttribute('questions_count', 2);
        $exam->setAttribute('attempts_count', 4);

        $data = $this->resolveResource(new ExamResource($exam));

        $this->assertSame([
            'id' => 10,
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'status' => 'published',
            'published_at' => '2026-10-16T10:00:00+00:00',
            'questions_count' => 2,
            'attempts_count' => 4,
            'created_at' => '2026-10-16T09:00:00+00:00',
            'updated_at' => '2026-10-16T09:30:00+00:00',
        ], array_diff_key($data, ['questions' => true]));
        $this->assertSame(
            ['id' => 12, 'position' => 2, 'text' => 'Brasília', 'is_correct' => true],
            $data['questions'][0]['options'][1],
        );
    }

    public function test_exam_resource_omits_counts_and_questions_not_loaded(): void
    {
        $exam = Exam::factory()->draft()->make(['id' => 10, 'teacher_id' => 1]);

        $data = $this->resolveResource(new ExamResource($exam));

        $this->assertSame('draft', $data['status']);
        $this->assertNull($data['published_at']);
        $this->assertArrayNotHasKey('questions_count', $data);
        $this->assertArrayNotHasKey('attempts_count', $data);
        $this->assertArrayNotHasKey('questions', $data);
    }

    public function test_question_and_option_resources(): void
    {
        $question = $this->examWithQuestions()->questions->first();

        $this->assertSame([
            'id' => 1,
            'position' => 1,
            'statement' => 'Qual é a capital do Brasil?',
            'options' => [
                ['id' => 11, 'position' => 1, 'text' => 'São Paulo', 'is_correct' => false],
                ['id' => 12, 'position' => 2, 'text' => 'Brasília', 'is_correct' => true],
            ],
        ], $this->resolveResource(new QuestionResource($question)));

        $this->assertArrayNotHasKey('options', $this->resolveResource(new QuestionResource(Question::factory()->make(['id' => 5, 'exam_id' => 10]))));
        $this->assertSame(
            ['id' => 9, 'position' => 3, 'text' => 'Recife', 'is_correct' => false],
            $this->resolveResource(new OptionResource(Option::factory()->make(['id' => 9, 'question_id' => 1, 'position' => 3, 'text' => 'Recife']))),
        );
    }

    public function test_exam_summary_resource(): void
    {
        $exam = $this->examWithQuestions();
        $exam->setAttribute('questions_count', 2);
        $exam->setAttribute('attempts_count', 3);

        $this->assertSame([
            'id' => 10,
            'title' => 'Prova de Geografia',
            'status' => 'draft',
            'published_at' => null,
            'questions_count' => 2,
            'attempts_count' => 3,
            'created_at' => '2026-10-16T09:00:00+00:00',
        ], $this->resolveResource(new ExamSummaryResource($exam)));
    }

    public function test_exam_summary_defaults_counts_to_zero(): void
    {
        $data = $this->resolveResource(new ExamSummaryResource(Exam::factory()->make(['id' => 10, 'teacher_id' => 1])));

        $this->assertSame(0, $data['questions_count']);
        $this->assertSame(0, $data['attempts_count']);
    }

    public function test_user_resource_hides_sensitive_fields(): void
    {
        $user = User::factory()->student()->make(['id' => 3, 'name' => 'Ana Souza', 'email' => 'ana@fenix.local']);

        $this->assertSame(
            ['id' => 3, 'name' => 'Ana Souza', 'email' => 'ana@fenix.local'],
            $this->resolveResource(new UserResource($user)),
        );
    }
}
