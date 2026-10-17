<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Resources\DashboardSummaryResource;
use App\Http\Resources\ExamRankingItemResource;
use App\Http\Services\Dashboard\GetDashboardExamsRankingService;
use App\Http\Services\Dashboard\GetDashboardSummaryService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    use HttpResponses;

    public function summary(User $teacher, GetDashboardSummaryService $service): JsonResponse
    {
        return $this->response(
            'Dashboard carregado com sucesso.',
            Response::HTTP_OK,
            new DashboardSummaryResource($service->handle($teacher)),
        );
    }

    public function examsRanking(PaginationRequest $request, User $teacher, GetDashboardExamsRankingService $service): JsonResponse
    {
        return $this->paginated(
            'Ranking das provas carregado com sucesso.',
            $service->handle($teacher, $request->page(), $request->perPage()),
            ExamRankingItemResource::class,
        );
    }
}
