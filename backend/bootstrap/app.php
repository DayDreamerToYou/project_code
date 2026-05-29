<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            \Illuminate\Session\Middleware\StartSession::class,
        ]);

        $middleware->alias([
            'session.auth' => \App\Http\Middleware\CheckSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => '数据验证失败',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Exception $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => config('app.debug') ? $e->getMessage() : '服务器错误，请稍后重试',
                ], 500);
            }
        });
    })->create();
