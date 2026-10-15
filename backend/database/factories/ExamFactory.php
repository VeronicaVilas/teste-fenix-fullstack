<?php

namespace Database\Factories;

use App\Enums\ExamStatus;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Sequence;

/**
 * @extends Factory<Exam>
 */
class ExamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'status' => ExamStatus::Draft,
            'published_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ExamStatus::Published,
            'published_at' => now(),
        ]);
    }

    public function withQuestions(int $questions = 3, int $options = 4): static
    {
        return $this->has(
            Question::factory()
                ->count($questions)
                ->sequence(fn (Sequence $sequence) => ['position' => $sequence->index % $questions + 1])
                ->withOptions($options),
            'questions',
        );
    }
}
