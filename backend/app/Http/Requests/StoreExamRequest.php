<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreExamRequest extends FormRequest
{
    public const ONE_CORRECT_OPTION_MESSAGE = 'Cada questão deve ter exatamente uma alternativa correta.';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->examRules();
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ($this->input('questions', []) as $index => $question) {
                    if (! is_array($question['options'] ?? null)) {
                        continue;
                    }

                    $correctOptions = collect($question['options'])
                        ->filter(fn ($option) => filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))
                        ->count();

                    if ($correctOptions !== 1) {
                        $validator->errors()->add("questions.{$index}.options", self::ONE_CORRECT_OPTION_MESSAGE);
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'title.string' => 'O título deve ser um texto.',
            'title.max' => 'O título deve ter no máximo 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto.',
            'description.max' => 'A descrição deve ter no máximo 2000 caracteres.',
            'questions.required' => 'A prova deve ter pelo menos uma questão.',
            'questions.array' => 'As questões devem ser enviadas em uma lista.',
            'questions.min' => 'A prova deve ter pelo menos uma questão.',
            'questions.max' => 'A prova deve ter no máximo 100 questões.',
            'questions.*.statement.required' => 'O enunciado da questão é obrigatório.',
            'questions.*.statement.string' => 'O enunciado da questão deve ser um texto.',
            'questions.*.statement.max' => 'O enunciado da questão deve ter no máximo 2000 caracteres.',
            'questions.*.options.required' => 'A questão deve ter alternativas.',
            'questions.*.options.array' => 'As alternativas devem ser enviadas em uma lista.',
            'questions.*.options.min' => 'A questão deve ter pelo menos 2 alternativas.',
            'questions.*.options.max' => 'A questão deve ter no máximo 5 alternativas.',
            'questions.*.options.*.text.required' => 'O texto da alternativa é obrigatório.',
            'questions.*.options.*.text.string' => 'O texto da alternativa deve ser um texto.',
            'questions.*.options.*.text.max' => 'O texto da alternativa deve ter no máximo 500 caracteres.',
            'questions.*.options.*.is_correct.required' => 'Informe se a alternativa é correta.',
            'questions.*.options.*.is_correct.boolean' => 'O campo de alternativa correta deve ser verdadeiro ou falso.',
        ];
    }

    protected function examRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'questions' => ['bail', 'required', 'array', 'min:1', 'max:100'],
            'questions.*.statement' => ['required', 'string', 'max:2000'],
            'questions.*.options' => ['bail', 'required', 'array', 'min:2', 'max:5'],
            'questions.*.options.*.text' => ['required', 'string', 'max:500'],
            'questions.*.options.*.is_correct' => ['required', 'boolean'],
        ];
    }
}
