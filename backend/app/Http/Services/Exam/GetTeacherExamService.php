<?php

namespace App\Http\Services\Exam;

use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;

final readonly class GetTeacherExamService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, int $examId): Exam
    {
        $exam = $this->examRepository->findForTeacher($teacher, $examId);

        return $this->examRepository->loadDetails($exam);
    }
}
