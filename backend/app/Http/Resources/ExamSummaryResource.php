<?php

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exam
 */
class ExamSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'questions_count' => $this->questions_count ?? 0,
            'attempts_count' => $this->attempts_count ?? 0,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
