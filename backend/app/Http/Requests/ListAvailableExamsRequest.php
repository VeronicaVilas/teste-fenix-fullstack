<?php

namespace App\Http\Requests;

use App\Enums\StudentExamStatus;
use Illuminate\Validation\Rule;

class ListAvailableExamsRequest extends SearchPaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::enum(StudentExamStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.enum' => 'A situação deve ser pending ou answered.',
        ];
    }

    public function status(): ?StudentExamStatus
    {
        return $this->enum('status', StudentExamStatus::class);
    }
}
