<?php

namespace Tests\Unit;

use App\Enums\ExamStatus;
use App\Models\Attempt;
use App\Models\Exam;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use ValueError;

class ExamTest extends TestCase
{
    public function test_status_enum_has_only_draft_and_published(): void
    {
        $values = array_map(fn (ExamStatus $status) => $status->value, ExamStatus::cases());

        $this->assertEquals(['draft', 'published'], $values);
    }

    public function test_status_defaults_to_draft(): void
    {
        $exam = new Exam;

        $this->assertSame(ExamStatus::Draft, $exam->status);
        $this->assertFalse($exam->isPublished());
    }

    public function test_published_exam_is_identified(): void
    {
        $exam = new Exam(['status' => ExamStatus::Published]);

        $this->assertTrue($exam->isPublished());
    }

    public function test_status_string_is_cast_to_enum(): void
    {
        $exam = new Exam(['status' => 'published']);

        $this->assertSame(ExamStatus::Published, $exam->status);
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->expectException(ValueError::class);

        new Exam(['status' => 'archived']);
    }

    public function test_published_at_is_cast_to_datetime(): void
    {
        $exam = new Exam(['published_at' => '2026-10-15 10:00:00']);

        $this->assertInstanceOf(Carbon::class, $exam->published_at);
    }

    public function test_teacher_relation(): void
    {
        $relation = (new Exam)->teacher();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(User::class, $relation->getRelated());
        $this->assertSame('teacher_id', $relation->getForeignKeyName());
    }

    public function test_questions_relation_is_ordered_by_position(): void
    {
        $relation = (new Exam)->questions();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Question::class, $relation->getRelated());
        $this->assertSame('exam_id', $relation->getForeignKeyName());
        $this->assertSame('position', $relation->toBase()->orders[0]['column']);
    }

    public function test_attempts_relation(): void
    {
        $relation = (new Exam)->attempts();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Attempt::class, $relation->getRelated());
        $this->assertSame('exam_id', $relation->getForeignKeyName());
    }

    public function test_published_scope_filters_by_status(): void
    {
        $wheres = Exam::query()->published()->toBase()->wheres;

        $this->assertSame('status', $wheres[0]['column']);
        $this->assertSame(ExamStatus::Published, $wheres[0]['value']);
    }

    public function test_factory_states_without_database(): void
    {
        $this->assertSame(ExamStatus::Draft, Exam::factory()->make(['teacher_id' => 1])->status);
        $this->assertSame(ExamStatus::Draft, Exam::factory()->draft()->make(['teacher_id' => 1])->status);

        $published = Exam::factory()->published()->make(['teacher_id' => 1]);

        $this->assertSame(ExamStatus::Published, $published->status);
        $this->assertNotNull($published->published_at);
    }
}
