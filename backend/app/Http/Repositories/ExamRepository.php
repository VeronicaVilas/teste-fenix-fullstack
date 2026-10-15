<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatus;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExamRepository implements ExamRepositoryInterface
{
    public function create(array $data): Exam
    {
        return Exam::create($data);
    }

    public function createQuestions(Exam $exam, array $questions): void
    {
        foreach (array_values($questions) as $index => $question) {
            $createdQuestion = $exam->questions()->create([
                'statement' => $question['statement'],
                'position' => $index + 1,
            ]);

            $createdQuestion->options()->createMany(
                collect($question['options'])
                    ->values()
                    ->map(fn (array $option, int $position) => [
                        'text' => $option['text'],
                        'position' => $position + 1,
                        'is_correct' => (bool) $option['is_correct'],
                    ])
                    ->all()
            );
        }
    }

    public function loadDetails(Exam $exam): Exam
    {
        return $exam->load('questions.options')->loadCount('questions');
    }

    public function paginateByTeacher(User $teacher, ?ExamStatus $status, int $perPage): LengthAwarePaginator
    {
        return $teacher->exams()
            ->withCount(['questions', 'attempts'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->latest('id')
            ->paginate($perPage);
    }
}
