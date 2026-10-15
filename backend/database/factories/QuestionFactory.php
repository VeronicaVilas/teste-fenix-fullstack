<?php

namespace Database\Factories;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'exam_id' => Exam::factory(),
            'statement' => fake()->sentence().'?',
            'position' => 1,
        ];
    }

    public function withOptions(int $options = 4): static
    {
        return $this->has(
            Option::factory()
                ->count($options)
                ->sequence(fn (Sequence $sequence) => [
                    'position' => $sequence->index % $options + 1,
                    'is_correct' => $sequence->index % $options === 0,
                ]),
            'options',
        );
    }
}
