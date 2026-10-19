<?php

namespace App\Http\Requests;

class SearchPaginationRequest extends PaginationRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'search' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'search.string' => 'A busca deve ser um texto.',
            'search.max' => 'A busca deve ter no máximo 255 caracteres.',
        ];
    }

    public function search(): ?string
    {
        $search = trim($this->string('search')->value());

        return $search === '' ? null : $search;
    }
}
