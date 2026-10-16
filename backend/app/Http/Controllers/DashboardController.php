<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardFilterRequest;
use App\Http\Resources\DashboardSummaryResource;
use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    use HttpResponses;

    public function summary(DashboardFilterRequest $request, User $teacher, GetDashboardSummaryService $service): JsonResponse
    {
        return $this->response(
            'Dashboard carregado com sucesso.',
            Response::HTTP_OK,
            new DashboardSummaryResource($service->handle($teacher, $request->examId())),
        );
    }
}
