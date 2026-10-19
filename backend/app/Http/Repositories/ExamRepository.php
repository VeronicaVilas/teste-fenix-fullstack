<?php

namespace App\Http\Repositories;

use App\Enums\ExamStatus;
use App\Enums\StudentExamStatus;
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

    public function findForTeacher(User $teacher, int $examId): Exam
    {
        return $teacher->exams()->findOrFail($examId);
    }

    public function lockForUpdate(Exam $exam): Exam
    {
        return Exam::query()->lockForUpdate()->findOrFail($exam->id);
    }

    public function update(Exam $exam, array $data): Exam
    {
        $exam->update($data);

        return $exam;
    }

    public function deleteQuestions(Exam $exam): void
    {
        $exam->questions()->delete();
    }

    public function publish(Exam $exam): Exam
    {
        $exam->update([
            'status' => ExamStatus::Published,
            'published_at' => now(),
        ]);

        return $exam;
    }

    public function hasAttempts(Exam $exam): bool
    {
        return $exam->attempts()->exists();
    }

    public function delete(Exam $exam): void
    {
        $exam->delete();
    }

    public function loadDetails(Exam $exam): Exam
    {
        return $exam->load('questions.options')->loadCount(['questions', 'attempts']);
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

    public function paginatePublishedForStudent(User $student, ?StudentExamStatus $status, ?string $search, int $perPage): LengthAwarePaginator
    {
        $studentAttempts = fn ($query) => $query->where('student_id', $student->id);

        return Exam::query()
            ->published()
            ->withCount('questions')
            ->with(['attempts' => $studentAttempts])
            ->when($status === StudentExamStatus::Answered, fn ($query) => $query->whereHas('attempts', $studentAttempts))
            ->when($status === StudentExamStatus::Pending, fn ($query) => $query->whereDoesntHave('attempts', $studentAttempts))
            ->when($search, fn ($query) => $query->whereLike('title', '%'.addcslashes($search, '%_\\').'%'))
            ->latest('published_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findPublishedWithQuestions(int $examId): Exam
    {
        return Exam::query()
            ->published()
            ->with('questions.options')
            ->withCount('questions')
            ->findOrFail($examId);
    }
}
