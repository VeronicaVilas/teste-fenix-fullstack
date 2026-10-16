<?php

namespace App\Http\Resources;

use App\Models\Attempt;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attempt
 */
class AttemptResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $answers = $this->answers->keyBy('question_id');

        return [
            'id' => $this->id,
            'exam' => [
                'id' => $this->exam->id,
                'title' => $this->exam->title,
            ],
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => $this->percentage,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'questions' => $this->exam->questions->map(fn (Question $question) => [
                'id' => $question->id,
                'position' => $question->position,
                'statement' => $question->statement,
                'selected_option_id' => $answers->get($question->id)?->option_id,
                'correct_option_id' => $question->options->firstWhere('is_correct', true)?->id,
                'is_correct' => (bool) $answers->get($question->id)?->is_correct,
                'options' => $question->options->map(fn (Option $option) => [
                    'id' => $option->id,
                    'position' => $option->position,
                    'text' => $option->text,
                    'is_correct' => $option->is_correct,
                ])->all(),
            ])->all(),
        ];
    }
}
