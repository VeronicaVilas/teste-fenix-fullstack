<?php

namespace App\Http\Services\Exam;

use App\Enums\ExamStatus;
use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListTeacherExamsService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $teacher, ?ExamStatus $status, int $perPage): LengthAwarePaginator
    {
        return $this->examRepository->paginateByTeacher($teacher, $status, $perPage);
    }
}
