<?php

namespace App\Interfaces;

use App\Models\Attempt;

interface AttemptRepositoryInterface
{
    public function existsFor(int $examId, int $studentId): bool;

    public function create(array $data): Attempt;

    public function createAnswers(Attempt $attempt, array $answers): void;

    public function findForStudentWithReview(int $examId, int $studentId): ?Attempt;
}
