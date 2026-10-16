<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitAttemptRequest;
use App\Http\Resources\AttemptResource;
use App\Http\Resources\AttemptResultResource;
use App\Http\Services\Attempt\GetAttemptResultService;
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

    public function show(User $student, int $exam, GetAttemptResultService $service): JsonResponse
    {
        return $this->response(
            'Resultado encontrado.',
            Response::HTTP_OK,
            new AttemptResultResource($service->handle($student, $exam)),
        );
    }
}
