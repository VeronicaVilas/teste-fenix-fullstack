<?php

namespace Database\Factories;

use App\Models\Attempt;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attempt>
 */
class AttemptFactory extends Factory
{
    public function definition(): array
    {
        $total = fake()->numberBetween(1, 10);
        $score = fake()->numberBetween(0, $total);

        return [
            'exam_id' => Exam::factory()->published(),
            'student_id' => User::factory()->student(),
            'score' => $score,
            'total_questions' => $total,
            'percentage' => round($score / $total * 100, 2),
            'submitted_at' => now(),
        ];
    }

    public function scored(int $score, int $total): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'total_questions' => $total,
            'percentage' => round($score / $total * 100, 2),
        ]);
    }
}
