<?php

namespace App\Http\Services\Exam;

use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class CreateExamService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, array $data): Exam
    {
        return DB::transaction(function () use ($teacher, $data) {
            $exam = $this->examRepository->create([
                'teacher_id' => $teacher->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            $this->examRepository->createQuestions($exam, $data['questions']);

            return $this->examRepository->loadDetails($exam);
        });
    }
}
