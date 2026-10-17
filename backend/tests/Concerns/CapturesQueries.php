<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;
use Throwable;

trait CapturesQueries
{
    /**
     * @return array<int, array{query: string, bindings: array}>
     */
    protected function captureQueries(callable $callback): array
    {
        return DB::pretend(function () use ($callback) {
            try {
                $callback();
            } catch (Throwable) {
            }
        });
    }

    protected function sql(array $queries, int $index = 0): string
    {
        return strtolower(str_replace('"', '', $queries[$index]['query']));
    }
}
