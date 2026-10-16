<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @property array<string, mixed> $resource
 */
class RankingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position' => (int) $this->resource['position'],
            'attempt_id' => (int) $this->resource['attempt_id'],
            'student_name' => $this->resource['student_name'],
            'exam_title' => $this->resource['exam_title'],
            'score' => (int) $this->resource['score'],
            'total_questions' => (int) $this->resource['total_questions'],
            'percentage' => number_format((float) $this->resource['percentage'], 2, '.', ''),
            'submitted_at' => Carbon::parse($this->resource['submitted_at'])->toIso8601String(),
        ];
    }
}
