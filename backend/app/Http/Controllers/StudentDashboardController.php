<?php

namespace App\Http\Controllers;

use App\Http\Resources\StudentDashboardResource;
use App\Http\Services\Dashboard\GetStudentDashboardService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StudentDashboardController extends Controller
{
    use HttpResponses;

    public function summary(User $student, GetStudentDashboardService $service): JsonResponse
    {
        return $this->response(
            'Dashboard carregado com sucesso.',
            Response::HTTP_OK,
            new StudentDashboardResource($service->handle($student)),
        );
    }
}
