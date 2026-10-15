<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Resources\UserResource;
use App\Http\Services\User\ListUsersByRoleService;
use App\Traits\HttpResponses;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class TeacherController extends Controller
{
    use HttpResponses;

    public function index(ListUsersByRoleService $service): JsonResponse
    {
        return $this->response(
            'Professores listados com sucesso.',
            Response::HTTP_OK,
            UserResource::collection($service->handle(UserRole::Teacher)),
        );
    }
}
