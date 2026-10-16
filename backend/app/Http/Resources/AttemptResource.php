<?php

namespace App\Http\Resources;

use App\Models\Attempt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Attempt
 */
class AttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'score' => $this->score,
            'total_questions' => $this->total_questions,
            'percentage' => $this->percentage,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
        ];
    }
}
