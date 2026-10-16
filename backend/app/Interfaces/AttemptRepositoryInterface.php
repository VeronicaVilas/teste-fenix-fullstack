<?php

namespace App\Interfaces;

interface AttemptRepositoryInterface
{
    public function existsFor(int $examId, int $studentId): bool;
}
