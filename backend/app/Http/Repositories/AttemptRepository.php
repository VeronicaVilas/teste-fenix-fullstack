<?php

namespace App\Http\Repositories;

use App\Interfaces\AttemptRepositoryInterface;
use App\Models\Attempt;

class AttemptRepository implements AttemptRepositoryInterface
{
    public function existsFor(int $examId, int $studentId): bool
    {
        return Attempt::query()
            ->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->exists();
    }

    public function create(array $data): Attempt
    {
        return Attempt::create($data);
    }

    public function createAnswers(Attempt $attempt, array $answers): void
    {
        $attempt->answers()->createMany($answers);
    }
}
