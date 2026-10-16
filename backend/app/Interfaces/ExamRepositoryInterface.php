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

    public function findForTeacher(User $teacher, int $examId): Exam;

    public function lockForUpdate(Exam $exam): Exam;

    public function update(Exam $exam, array $data): Exam;

    public function deleteQuestions(Exam $exam): void;

    public function publish(Exam $exam): Exam;

    public function hasAttempts(Exam $exam): bool;

    public function delete(Exam $exam): void;

    public function loadDetails(Exam $exam): Exam;

    public function paginateByTeacher(User $teacher, ?ExamStatus $status, int $perPage): LengthAwarePaginator;

    public function paginatePublishedForStudent(User $student, int $perPage): LengthAwarePaginator;

    public function findPublishedWithQuestions(int $examId): Exam;
}
