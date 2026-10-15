<?php

namespace App\Http\Services\Exam;

use App\Exceptions\ExamHasAttemptsException;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class DeleteExamService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, int $examId): void
    {
        $exam = $this->examRepository->findForTeacher($teacher, $examId);

        $exam = DB::transaction(function () use ($exam) {
            $exam = $this->examRepository->lockForUpdate($exam);

            if ($this->examRepository->hasAttempts($exam)) {
                throw new ExamHasAttemptsException;
            }

            $this->examRepository->delete($exam);

            return $exam;
        });

        if ($exam->isPublished()) {
            Cache::forget("exam:{$exam->id}:published");
            Cache::tags(['dashboard'])->flush();
        }
    }
}
