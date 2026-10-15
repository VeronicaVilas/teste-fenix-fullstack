<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Fênix API',
    description: 'API para gerenciamento de provas online.'
)]
#[OA\Server(
    url: '/api/v1',
    description: 'Fênix API v1'
)]
abstract class Controller
{
    //
}
