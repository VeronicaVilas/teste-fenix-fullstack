<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/up',
    operationId: 'healthCheck',
    summary: 'Verifica se a aplicação está no ar',
    description: 'Rota de health check nativa do Laravel, servida fora do prefixo /api/v1.',
    tags: ['Health'],
    servers: [new OA\Server(url: '/', description: 'Raiz da aplicação')],
    responses: [
        new OA\Response(response: 200, description: 'Aplicação disponível'),
        new OA\Response(response: 500, description: 'Falha ao inicializar a aplicação'),
    ]
)]
final class HealthCheckDocumentation {}
