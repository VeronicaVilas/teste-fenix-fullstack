<?php

namespace App\Http\Services\Exam;

use App\Interfaces\AttemptRepositoryInterface;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final readonly class GetAvailableExamService
{
    public function __construct(
        private ExamRepositoryInterface $examRepository,
        private AttemptRepositoryInterface $attemptRepository,
    ) {}

    /**
     * @return array{exam: Exam, answered: bool}
     */
    public function handle(User $student, int $examId): array
    {
        $exam = Cache::remember(
            "exam:{$examId}:published",
            now()->addHour(),
            fn () => $this->examRepository->findPublishedWithQuestions($examId),
        );

        return [
            'exam' => $exam,
            'answered' => $this->attemptRepository->existsFor($examId, $student->id),
        ];
    }
}
