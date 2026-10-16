<?php

namespace App\Http\Resources;

use App\Models\Exam;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Exam
 */
class AvailableExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attempt = $this->attempts->first();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'questions_count' => $this->questions_count ?? 0,
            'published_at' => $this->published_at?->toIso8601String(),
            'answered' => $attempt !== null,
            'attempt' => $attempt ? (new AttemptSummaryResource($attempt))->resolve($request) : null,
        ];
    }
}
