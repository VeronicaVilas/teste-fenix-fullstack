<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginationRequest;
use App\Http\Resources\AvailableExamResource;
use App\Http\Services\Exam\ListAvailableExamsService;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;

class StudentExamController extends Controller
{
    use HttpResponses;

    public function index(PaginationRequest $request, User $student, ListAvailableExamsService $service): JsonResponse
    {
        return $this->paginated(
            'Provas disponíveis listadas com sucesso.',
            $service->handle($student, $request->perPage()),
            AvailableExamResource::class,
        );
    }
}
