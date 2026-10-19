<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListAvailableExamsRequest;
use App\Http\Resources\AvailableExamResource;
use App\Http\Resources\StudentExamResource;
use App\Http\Services\Exam\GetAvailableExamService;
use App\Http\Services\Exam\ListAvailableExamsService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StudentExamController extends Controller
{
    use HttpResponses;

    public function index(ListAvailableExamsRequest $request, User $student, ListAvailableExamsService $service): JsonResponse
    {
        return $this->paginated(
            'Provas disponíveis listadas com sucesso.',
            $service->handle($student, $request->status(), $request->search(), $request->perPage()),
            AvailableExamResource::class,
        );
    }

    public function show(User $student, int $exam, GetAvailableExamService $service): JsonResponse
    {
        return $this->response(
            'Prova encontrada.',
            Response::HTTP_OK,
            new StudentExamResource($service->handle($student, $exam)),
        );
    }
}
