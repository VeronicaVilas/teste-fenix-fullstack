<?php

namespace Tests\Concerns;

use Illuminate\Http\Resources\Json\JsonResource;

trait ResolvesResources
{
    protected function resolveResource(JsonResource $resource): array
    {
        return json_decode(json_encode($resource->resolve()), true);
    }
}
