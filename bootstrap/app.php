<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        using: function () {
            Route::prefix('')
                ->group(base_path('routes/web.php'));


            Route::prefix('api')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'jwt' => \App\Http\Middleware\JwtMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

          // Handle authentication exceptions
          $exceptions->render(function (AuthenticationException $e, Request $request): Response {
            return response()->json([
                'message' => 'Unauthenticated.',
                'error' => $e->getMessage(),
            ], 401);
        });

        // Handle RouteNotFoundException explicitly
        $exceptions->render(function (RouteNotFoundException $e, Request $request) {
            return response()->json([
                'message' => 'Route not defined.',
                'error' => $e->getMessage(),
            ], 404);
        });

        // Handle generic HTTP 404 errors as JSON
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            return response()->json([
                'message' => 'Not found.',
                'error' => $e->getMessage(),
            ], 404);
        });

        // Catch-all for debugging purposes (optional)
        $exceptions->render(function (Throwable $e, Request $request) {
            return response()->json([
                'message' => 'Server Error.',
                'error' => $e->getMessage(),
                'type' => get_class($e)
            ], 500);
        });

    })->create();