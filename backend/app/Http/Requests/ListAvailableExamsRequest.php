<?php

namespace App\Http\Requests;

use App\Enums\StudentExamStatus;
use Illuminate\Validation\Rule;

class ListAvailableExamsRequest extends PaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['nullable', Rule::enum(StudentExamStatus::class)],
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.enum' => 'A situação deve ser pending ou answered.',
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca deve ter no máximo 255 caracteres.',
        ];
    }

    public function status(): ?StudentExamStatus
    {
        return $this->enum('status', StudentExamStatus::class);
    }

    public function search(): ?string
    {
        $search = trim($this->string('search')->value());

        return $search === '' ? null : $search;
    }
}
