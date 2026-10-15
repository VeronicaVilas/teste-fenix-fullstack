<?php

namespace Tests\Unit;

use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class OptionTest extends TestCase
{
    public function test_is_correct_defaults_to_false(): void
    {
        $this->assertFalse((new Option)->is_correct);
    }

    public function test_casts(): void
    {
        $option = new Option(['is_correct' => 1, 'position' => '3']);

        $this->assertTrue($option->is_correct);
        $this->assertSame(3, $option->position);
    }

    public function test_question_relation(): void
    {
        $relation = (new Option)->question();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Question::class, $relation->getRelated());
        $this->assertSame('question_id', $relation->getForeignKeyName());
    }

    public function test_correct_factory_state_without_database(): void
    {
        $this->assertFalse(Option::factory()->make(['question_id' => 1])->is_correct);
        $this->assertTrue(Option::factory()->correct()->make(['question_id' => 1])->is_correct);
    }
}
