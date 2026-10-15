<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

trait HttpResponses
{
    public function response(string $message, int $status, array|Model|JsonResource $data = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
            'data' => $data,
        ], $status);
    }

    public function error(string $message, int $status, array|MessageBag $errors = [], array $data = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'status' => $status,
            'errors' => $errors,
            'data' => $data,
        ], $status);
    }

    /**
     * @param  class-string<JsonResource>  $resourceClass
     */
    public function paginated(string $message, LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return $this->response($message, Response::HTTP_OK, [
            'items' => $resourceClass::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
