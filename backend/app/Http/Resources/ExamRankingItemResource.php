<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array<string, mixed> $resource
 */
class ExamRankingItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position' => (int) $this->resource['position'],
            'exam_id' => (int) $this->resource['exam_id'],
            'title' => $this->resource['title'],
            'students_count' => (int) $this->resource['students_count'],
            'average_percentage' => number_format((float) $this->resource['average_percentage'], 2, '.', ''),
            'best_student_name' => $this->resource['best_student_name'],
        ];
    }
}
