<?php

namespace App\Http\Requests;

class DashboardFilterRequest extends PaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'exam_id' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'exam_id.integer' => 'A prova deve ser informada pelo id numérico.',
            'exam_id.min' => 'A prova deve ser informada pelo id numérico.',
        ];
    }

    public function examId(): ?int
    {
        return $this->filled('exam_id') ? $this->integer('exam_id') : null;
    }

    public function page(): int
    {
        return $this->integer('page', 1);
    }
}
