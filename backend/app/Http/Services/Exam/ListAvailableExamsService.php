<?php

namespace App\Http\Services\Exam;

use App\Interfaces\ExamRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListAvailableExamsService
{
    public function __construct(private ExamRepositoryInterface $examRepository) {}

    public function handle(User $student, int $perPage): LengthAwarePaginator
    {
        return $this->examRepository->paginatePublishedForStudent($student, $perPage);
    }
}
