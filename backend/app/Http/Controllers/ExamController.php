<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExamRequest;
use App\Http\Resources\ExamResource;
use App\Http\Services\Exam\CreateExamService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExamController extends Controller
{
    use HttpResponses;

    public function store(StoreExamRequest $request, User $teacher, CreateExamService $service): JsonResponse
    {
        return $this->response(
            'Prova criada com sucesso.',
            Response::HTTP_CREATED,
            new ExamResource($service->handle($teacher, $request->validated())),
        );
    }
}
