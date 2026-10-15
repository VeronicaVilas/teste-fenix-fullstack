<?php

namespace App\Interfaces;

use App\Models\Exam;

interface ExamRepositoryInterface
{
    public function create(array $data): Exam;

    public function createQuestions(Exam $exam, array $questions): void;

    public function loadDetails(Exam $exam): Exam;
}
