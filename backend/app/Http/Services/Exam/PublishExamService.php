<?php

namespace App\Http\Services\Exam;

use App\Exceptions\ExamNotEditableException;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class PublishExamService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, int $examId): Exam
    {
        $exam = $this->examRepository->findForTeacher($teacher, $examId);

        $exam = DB::transaction(function () use ($exam) {
            $exam = $this->examRepository->lockForUpdate($exam);

            if ($exam->isPublished()) {
                throw new ExamNotEditableException;
            }

            return $this->examRepository->publish($exam);
        });

        Cache::tags(['dashboard'])->flush();

        return $this->examRepository->loadDetails($exam);
    }
}
