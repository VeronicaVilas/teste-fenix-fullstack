<?php

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{exam: Exam, answered: bool} $resource
 */
class StudentExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $exam = $this->resource['exam'];

        return [
            'id' => $exam->id,
            'title' => $exam->title,
            'description' => $exam->description,
            'questions_count' => $exam->questions_count ?? $exam->questions->count(),
            'answered' => $this->resource['answered'],
            'questions' => StudentQuestionResource::collection($exam->questions),
        ];
    }
}
