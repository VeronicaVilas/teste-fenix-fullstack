<?php

namespace Tests\Unit;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttemptTest extends TestCase
{
    public function test_casts(): void
    {
        $attempt = new Attempt([
            'score' => '4',
            'total_questions' => '5',
            'percentage' => 80,
            'submitted_at' => '2026-10-15 10:00:00',
        ]);

        $this->assertSame(4, $attempt->score);
        $this->assertSame(5, $attempt->total_questions);
        $this->assertSame('80.00', $attempt->percentage);
        $this->assertInstanceOf(Carbon::class, $attempt->submitted_at);
    }

    public function test_exam_relation(): void
    {
        $relation = (new Attempt)->exam();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Exam::class, $relation->getRelated());
        $this->assertSame('exam_id', $relation->getForeignKeyName());
    }

    public function test_student_relation(): void
    {
        $relation = (new Attempt)->student();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('student_id', $relation->getForeignKeyName());
    }

    public function test_answers_relation(): void
    {
        $relation = (new Attempt)->answers();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(AttemptAnswer::class, $relation->getRelated());
        $this->assertSame('attempt_id', $relation->getForeignKeyName());
    }

    public function test_scored_factory_state_calculates_percentage(): void
    {
        $attempt = Attempt::factory()->scored(2, 3)->make(['exam_id' => 1, 'student_id' => 1]);

        $this->assertSame(2, $attempt->score);
        $this->assertSame(3, $attempt->total_questions);
        $this->assertSame('66.67', $attempt->percentage);
    }
}
