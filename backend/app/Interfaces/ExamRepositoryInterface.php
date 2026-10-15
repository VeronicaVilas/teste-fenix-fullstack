<?php

namespace App\Interfaces;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ExamRepositoryInterface
{
    public function create(array $data): Exam;

    public function createQuestions(Exam $exam, array $questions): void;

    public function loadDetails(Exam $exam): Exam;

    public function paginateByTeacher(User $teacher, ?ExamStatus $status, int $perPage): LengthAwarePaginator;
}
