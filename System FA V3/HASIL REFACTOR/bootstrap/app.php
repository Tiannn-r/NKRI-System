<?php

@ini_set('memory_limit', '512M');

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
    ->withMiddleware(function (Middleware $middleware) {
        // Disable CSRF for API endpoints when consumed by external clients/tablets
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Log all uncaught exceptions to SystemErrorLog for the /admin diagnostics dashboard
        $exceptions->reportable(function (Throwable $e) {
            try {
                if (class_exists(\App\Models\SystemErrorLog::class)) {
                    \App\Models\SystemErrorLog::create([
                        'level' => 'ERROR',
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => substr($e->getTraceAsString(), 0, 5000),
                        'endpoint' => request()->path(),
                        'method' => request()->method(),
                        'username' => auth()->user()?->username ?? 'unauthenticated',
                        'ip_address' => request()->ip(),
                    ]);
                }
            } catch (\Throwable $loggingError) {
                // Ignore if database connection is failing
            }
        });
    })->create();
