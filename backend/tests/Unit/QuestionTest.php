<?php

namespace Tests\Unit;

use App\Models\Exam;
use App\Models\Option;
use App\Models\Question;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class QuestionTest extends TestCase
{
    public function test_position_is_cast_to_integer(): void
    {
        $question = new Question(['position' => '2']);

        $this->assertSame(2, $question->position);
    }

    public function test_exam_relation(): void
    {
        $relation = (new Question)->exam();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertInstanceOf(Exam::class, $relation->getRelated());
        $this->assertSame('exam_id', $relation->getForeignKeyName());
    }

    public function test_options_relation_is_ordered_by_position(): void
    {
        $relation = (new Question)->options();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertInstanceOf(Option::class, $relation->getRelated());
        $this->assertSame('question_id', $relation->getForeignKeyName());
        $this->assertSame('position', $relation->toBase()->orders[0]['column']);
    }

    public function test_correct_option_relation_filters_correct_option(): void
    {
        $relation = (new Question)->correctOption();
        $where = collect($relation->toBase()->wheres)->firstWhere('column', 'is_correct');

        $this->assertInstanceOf(HasOne::class, $relation);
        $this->assertInstanceOf(Option::class, $relation->getRelated());
        $this->assertTrue($where['value']);
    }
}
