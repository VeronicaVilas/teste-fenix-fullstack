<?php

namespace Tests\Unit;

use App\Models\Attempt;
use App\Models\AttemptAnswer;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class AttemptAnswerTest extends TestCase
{
    public function test_is_correct_is_cast_to_boolean(): void
    {
        $this->assertTrue((new AttemptAnswer(['is_correct' => 1]))->is_correct);
        $this->assertFalse((new AttemptAnswer(['is_correct' => 0]))->is_correct);
    }

    public function test_relations(): void
    {
        $answer = new AttemptAnswer;

        $relations = [
            'attempt_id' => [$answer->attempt(), Attempt::class],
            'question_id' => [$answer->question(), Question::class],
            'option_id' => [$answer->option(), Option::class],
        ];

        foreach ($relations as $foreignKey => [$relation, $related]) {
            $this->assertInstanceOf(BelongsTo::class, $relation);
            $this->assertInstanceOf($related, $relation->getRelated());
            $this->assertSame($foreignKey, $relation->getForeignKeyName());
        }
    }
}
