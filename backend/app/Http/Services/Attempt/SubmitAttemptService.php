<?php

namespace App\Http\Services\Attempt;

use App\Exceptions\AttemptAlreadySubmittedException;
use App\Exceptions\InvalidAnswersException;
use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class SubmitAttemptService
{
    public function __construct(
        private ExamRepositoryInterface $examRepository,
        private AttemptRepositoryInterface $attemptRepository,
    ) {}

    /**
     * @param  array<int, array{question_id: int, option_id: int}>  $answers
     */
    public function handle(User $student, int $examId, array $answers): Attempt
    {
        $exam = $this->examRepository->findPublishedWithQuestions($examId);

        if ($this->attemptRepository->existsFor($exam->id, $student->id)) {
            throw new AttemptAlreadySubmittedException;
        }

        $graded = $this->grade($exam, $answers);

        try {
            $attempt = DB::transaction(function () use ($exam, $student, $graded) {
                $attempt = $this->attemptRepository->create([
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'score' => $graded['score'],
                    'total_questions' => $graded['total'],
                    'percentage' => $graded['percentage'],
                    'submitted_at' => now(),
                ]);

                $this->attemptRepository->createAnswers($attempt, $graded['answers']);

                return $attempt;
            });
        } catch (UniqueConstraintViolationException) {
            throw new AttemptAlreadySubmittedException;
        }

        Cache::tags(['dashboard'])->flush();

        return $attempt;
    }

    /**
     * @return array{answers: array<int, array{question_id: int, option_id: int, is_correct: bool}>, score: int, total: int, percentage: float}
     */
    private function grade(Exam $exam, array $answers): array
    {
        $chosen = collect($answers)->pluck('option_id', 'question_id');
        $questionIds = $exam->questions->pluck('id');

        if ($chosen->count() !== $questionIds->count() || $questionIds->diff($chosen->keys())->isNotEmpty()) {
            throw new InvalidAnswersException('Responda todas as questões da prova.');
        }

        $graded = $exam->questions->map(function (Question $question) use ($chosen) {
            $option = $question->options->firstWhere('id', (int) $chosen[$question->id])
                ?? throw new InvalidAnswersException("Alternativa inválida para a questão {$question->position}.");

            return [
                'question_id' => $question->id,
                'option_id' => $option->id,
                'is_correct' => $option->is_correct,
            ];
        });

        $score = $graded->where('is_correct', true)->count();
        $total = $questionIds->count();

        return [
            'answers' => $graded->all(),
            'score' => $score,
            'total' => $total,
            'percentage' => round($score / $total * 100, 2),
        ];
    }
}
