<?php

namespace Tests\Concerns;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

trait BuildsExams
{
    protected function examWithQuestions(array $attributes = []): Exam
    {
        $brazil = Question::factory()->make(['id' => 1, 'exam_id' => 10, 'position' => 1, 'statement' => 'Qual é a capital do Brasil?']);
        $brazil->setRelation('options', new Collection([
            Option::factory()->make(['id' => 11, 'question_id' => 1, 'position' => 1, 'text' => 'São Paulo']),
            Option::factory()->correct()->make(['id' => 12, 'question_id' => 1, 'position' => 2, 'text' => 'Brasília']),
        ]));

        $argentina = Question::factory()->make(['id' => 2, 'exam_id' => 10, 'position' => 2, 'statement' => 'Qual é a capital da Argentina?']);
        $argentina->setRelation('options', new Collection([
            Option::factory()->correct()->make(['id' => 21, 'question_id' => 2, 'position' => 1, 'text' => 'Buenos Aires']),
            Option::factory()->make(['id' => 22, 'question_id' => 2, 'position' => 2, 'text' => 'Lima']),
        ]));

        $exam = Exam::factory()->make([
            'id' => 10,
            'teacher_id' => 1,
            'title' => 'Prova de Geografia',
            'description' => 'Capitais',
            'created_at' => Carbon::parse('2026-10-16 09:00:00'),
            'updated_at' => Carbon::parse('2026-10-16 09:30:00'),
            ...$attributes,
        ]);
        $exam->setRelation('questions', new Collection([$brazil, $argentina]));

        return $exam;
    }

    protected function reviewedAttempt(): Attempt
    {
        $attempt = Attempt::factory()->scored(1, 2)->make([
            'id' => 7,
            'exam_id' => 10,
            'student_id' => 3,
            'submitted_at' => Carbon::parse('2026-10-16 10:00:00'),
        ]);
        $attempt->setRelation('exam', $this->examWithQuestions());
        $attempt->setRelation('answers', new Collection([
            new AttemptAnswer(['question_id' => 1, 'option_id' => 11, 'is_correct' => false]),
            new AttemptAnswer(['question_id' => 2, 'option_id' => 21, 'is_correct' => true]),
        ]));

        return $attempt;
    }
}
