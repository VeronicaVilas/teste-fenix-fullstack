<?php

namespace App\Http\Services\Exam;

use App\Exceptions\ExamNotEditableException;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpdateExamService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, int $examId, array $data): Exam
    {
        $exam = $this->examRepository->findForTeacher($teacher, $examId);

        return DB::transaction(function () use ($exam, $data) {
            $exam = $this->examRepository->lockForUpdate($exam);

            if ($exam->isPublished()) {
                throw new ExamNotEditableException;
            }

            $this->examRepository->update($exam, [
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);
            $this->examRepository->deleteQuestions($exam);
            $this->examRepository->createQuestions($exam, $data['questions']);

            return $this->examRepository->loadDetails($exam);
        });
    }
}
