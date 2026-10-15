<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApi = fn (Request $request) => $request->is('api/*') || $request->expectsJson();

        $exceptions->shouldRenderJsonWhen($isApi);

        $error = fn (string $message, int $status, array $errors = []) => response()->json([
            'message' => $message,
            'status' => $status,
            'errors' => $errors,
            'data' => [],
        ], $status);

        $exceptions->render(fn (ValidationException $e, Request $request) => $isApi($request)
            ? $error('Os dados enviados são inválidos.', Response::HTTP_UNPROCESSABLE_ENTITY, $e->errors())
            : null);

        $exceptions->render(fn (NotFoundHttpException $e, Request $request) => $isApi($request)
            ? $error('Recurso não encontrado.', Response::HTTP_NOT_FOUND)
            : null);

        $exceptions->render(fn (HttpExceptionInterface $e, Request $request) => $isApi($request)
            ? $error($e->getMessage() ?: 'Erro na requisição.', $e->getStatusCode())
            : null);

        $exceptions->render(function (Throwable $e, Request $request) use ($isApi, $error) {
            if (! $isApi($request) || config('app.debug')) {
                return null;
            }

            return $error('Erro interno no servidor.', Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
