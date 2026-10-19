<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{id: int|string, title: string, attempts_count: int|string, average_percentage: float|string|null} $resource
 */
class ExamAverageItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $average = $this->resource['average_percentage'];

        return [
            'id' => (int) $this->resource['id'],
            'title' => $this->resource['title'],
            'attempts_count' => (int) $this->resource['attempts_count'],
            'average_percentage' => $average === null ? null : number_format((float) $average, 2, '.', ''),
        ];
    }
}
