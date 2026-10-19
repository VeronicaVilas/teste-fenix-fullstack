<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{answered_count: int, pending_count: int, average_percentage: float|string|null, best_percentage: float|string|null} $resource
 */
class StudentDashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'answered_count' => (int) $this->resource['answered_count'],
            'pending_count' => (int) $this->resource['pending_count'],
            'average_percentage' => self::percentage($this->resource['average_percentage']),
            'best_percentage' => self::percentage($this->resource['best_percentage']),
        ];
    }

    private static function percentage(float|int|string|null $value): ?string
    {
        return $value === null ? null : number_format((float) $value, 2, '.', '');
    }
}
