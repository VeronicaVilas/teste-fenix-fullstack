<?php

namespace Tests\Unit\Http\Resources;

use App\Http\Resources\AttemptResource;
use App\Http\Resources\AttemptResultResource;
use App\Http\Resources\AttemptSummaryResource;
use App\Http\Resources\AvailableExamResource;
use App\Http\Resources\StudentExamResource;
use App\Models\Attempt;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Tests\Concerns\BuildsExams;
use Tests\Concerns\ResolvesResources;
use Tests\TestCase;

class StudentResourcesTest extends TestCase
{
    use BuildsExams, ResolvesResources;

    public function test_student_exam_resource_never_exposes_answer_key(): void
    {
        $exam = $this->examWithQuestions(['status' => 'published']);

        $data = $this->resolveResource(new StudentExamResource(['exam' => $exam, 'answered' => false]));

        $this->assertSame([
            'id' => 10,
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'questions_count' => 2,
            'answered' => false,
            'questions' => [
                [
                    'id' => 1,
                    'position' => 1,
                    'statement' => 'Qual é a capital do Brasil?',
                    'options' => [
                        ['id' => 11, 'position' => 1, 'text' => 'São Paulo'],
                        ['id' => 12, 'position' => 2, 'text' => 'Brasília'],
                    ],
                ],
                [
                    'id' => 2,
                    'position' => 2,
                    'statement' => 'Qual é a capital da Argentina?',
                    'options' => [
                        ['id' => 21, 'position' => 1, 'text' => 'Buenos Aires'],
                        ['id' => 22, 'position' => 2, 'text' => 'Lima'],
                    ],
                ],
            ],
        ], $data);
        $this->assertStringNotContainsString('is_correct', json_encode($data));
    }

    public function test_available_exam_with_and_without_attempt(): void
    {
        $answered = $this->examWithQuestions(['status' => 'published', 'published_at' => Carbon::parse('2026-10-16 08:00:00')]);
        $answered->setAttribute('questions_count', 2);
        $answered->setRelation('attempts', new Collection([$this->attempt()]));

        $pending = $this->examWithQuestions(['status' => 'published', 'published_at' => Carbon::parse('2026-10-16 08:00:00')]);
        $pending->setRelation('attempts', new Collection);

        $this->assertSame([
            'id' => 10,
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'questions_count' => 2,
            'published_at' => '2026-10-16T08:00:00+00:00',
            'answered' => true,
            'attempt' => ['score' => 1, 'total_questions' => 2, 'percentage' => '50.00', 'submitted_at' => '2026-10-16T10:00:00+00:00'],
        ], $this->resolveResource(new AvailableExamResource($answered)));

        $pendingData = $this->resolveResource(new AvailableExamResource($pending));
        $this->assertFalse($pendingData['answered']);
        $this->assertNull($pendingData['attempt']);
        $this->assertSame(0, $pendingData['questions_count']);
    }

    public function test_attempt_resources(): void
    {
        $attempt = $this->attempt();

        $this->assertSame([
            'id' => 7,
            'exam_id' => 10,
            'score' => 1,
            'total_questions' => 2,
            'percentage' => '50.00',
            'submitted_at' => '2026-10-16T10:00:00+00:00',
        ], $this->resolveResource(new AttemptResource($attempt)));

        $this->assertSame(
            ['score' => 1, 'total_questions' => 2, 'percentage' => '50.00', 'submitted_at' => '2026-10-16T10:00:00+00:00'],
            $this->resolveResource(new AttemptSummaryResource($attempt)),
        );
    }

    public function test_attempt_result_resource_reviews_each_question(): void
    {
        $data = $this->resolveResource(new AttemptResultResource($this->reviewedAttempt()));

        $this->assertSame(['id' => 10, 'title' => 'Prova de Geografia'], $data['exam']);
        $this->assertSame('50.00', $data['percentage']);
        $this->assertSame([
            'id' => 1,
            'position' => 1,
            'statement' => 'Qual é a capital do Brasil?',
            'selected_option_id' => 11,
            'correct_option_id' => 12,
            'is_correct' => false,
            'options' => [
                ['id' => 11, 'position' => 1, 'text' => 'São Paulo', 'is_correct' => false],
                ['id' => 12, 'position' => 2, 'text' => 'Brasília', 'is_correct' => true],
            ],
        ], $data['questions'][0]);
        $this->assertTrue($data['questions'][1]['is_correct']);
        $this->assertSame(21, $data['questions'][1]['selected_option_id']);
    }

    private function attempt(): Attempt
    {
        return Attempt::factory()->scored(1, 2)->make([
            'id' => 7,
            'exam_id' => 10,
            'student_id' => 3,
            'submitted_at' => Carbon::parse('2026-10-16 10:00:00'),
        ]);
    }
}
