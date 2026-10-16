<?php

namespace App\Http\Requests;

use App\Enums\ExamStatus;
use Illuminate\Validation\Rule;

class ListExamsRequest extends PaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::enum(ExamStatus::class)],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.enum' => 'O status deve ser draft ou published.',
        ];
    }
}
