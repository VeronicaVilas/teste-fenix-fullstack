<?php

namespace App\Http\Controllers;

use App\Enums\ExamStatus;
use App\Http\Requests\ListExamsRequest;
use App\Http\Requests\StoreExamRequest;
use App\Http\Resources\ExamResource;
use App\Http\Resources\ExamSummaryResource;
use App\Http\Services\Exam\CreateExamService;
use App\Http\Services\Exam\GetTeacherExamService;
use App\Http\Services\Exam\ListTeacherExamsService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExamController extends Controller
{
    use HttpResponses;

    public function index(ListExamsRequest $request, User $teacher, ListTeacherExamsService $service): JsonResponse
    {
        $exams = $service->handle(
            $teacher,
            $request->enum('status', ExamStatus::class),
            $request->integer('per_page', 10),
        );

        return $this->paginated('Provas listadas com sucesso.', $exams, ExamSummaryResource::class);
    }

    public function store(StoreExamRequest $request, User $teacher, CreateExamService $service): JsonResponse
    {
        return $this->response(
            'Prova criada com sucesso.',
            Response::HTTP_CREATED,
            new ExamResource($service->handle($teacher, $request->validated())),
        );
    }

    public function show(User $teacher, int $exam, GetTeacherExamService $service): JsonResponse
    {
        return $this->response(
            'Prova encontrada.',
            Response::HTTP_OK,
            new ExamResource($service->handle($teacher, $exam)),
        );
    }
}
