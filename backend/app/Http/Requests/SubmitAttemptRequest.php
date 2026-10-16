<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.option_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'answers.required' => 'Envie as respostas da prova.',
            'answers.array' => 'As respostas devem ser enviadas em uma lista.',
            'answers.min' => 'Envie as respostas da prova.',
            'answers.*.question_id.required' => 'Informe a questão respondida.',
            'answers.*.question_id.integer' => 'A questão deve ser um número inteiro.',
            'answers.*.question_id.distinct' => 'Cada questão deve ser respondida apenas uma vez.',
            'answers.*.option_id.required' => 'Informe a alternativa escolhida.',
            'answers.*.option_id.integer' => 'A alternativa deve ser um número inteiro.',
        ];
    }
}
