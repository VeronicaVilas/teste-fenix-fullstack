<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Http\Services\Attempt\SubmitAttemptService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AttemptController extends Controller
{
    use HttpResponses;

    public function store(SubmitAttemptRequest $request, User $student, int $exam, SubmitAttemptService $service): JsonResponse
    {
        return $this->response(
            'Prova enviada e corrigida com sucesso.',
            Response::HTTP_CREATED,
            new AttemptResource($service->handle($student, $exam, $request->validated('answers'))),
        );
    }
}
