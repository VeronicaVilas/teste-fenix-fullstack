<?php

namespace App\Http\Services\Attempt;

use App\Exceptions\AttemptNotFoundException;
use App\Interfaces\AttemptRepositoryInterface;
use App\Models\Attempt;
use App\Models\User;

final readonly class GetAttemptResultService
{
    public function __construct(private AttemptRepositoryInterface $attemptRepository) {}

    public function handle(User $student, int $examId): Attempt
    {
        return $this->attemptRepository->findForStudentWithReview($examId, $student->id)
            ?? throw new AttemptNotFoundException;
    }
}
