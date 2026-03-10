<?php

use App\Http\Middleware\ActivityLogger;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register route middleware aliases
        $middleware->alias([
            'role'         => RoleMiddleware::class,
            'activity.log' => ActivityLogger::class,
        ]);

        // Apply ActivityLogger to all API routes
        $middleware->appendToGroup('api', ActivityLogger::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Handle 404 Not Found
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Resource not found.',
                    'data'    => null,
                ], 404);
            }
        });

        // Handle 405 Method Not Allowed
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Method not allowed.',
                    'data'    => null,
                ], 405);
            }
        });

        // Handle general exceptions in API
        $exceptions->render(function (\Exception $e, Request $request) {
            if ($request->is('api/*') && config('app.debug') === false) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'An unexpected error occurred.',
                    'data'    => null,
                ], 500);
            }
        });
    })->create();
