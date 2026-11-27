<?php

use App\Http\Middleware\AsegurarCuentaHabilitada;
use App\Http\Middleware\AsegurarEmailVerificado;
use App\Http\Middleware\CabecerasSeguridad;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(CabecerasSeguridad::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AsegurarCuentaHabilitada::class,
        ]);

        $middleware->api(append: [
            AsegurarCuentaHabilitada::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'verified' => AsegurarEmailVerificado::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return $response;
            }

            if (! (app()->environment(['local', 'testing']) && config('app.debug'))) {
                if (in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                    return Inertia::render('Error', ['status' => $response->getStatusCode()])
                        ->toResponse($request)
                        ->setStatusCode($response->getStatusCode());
                }

                if ($response->getStatusCode() === 419) {
                    return back()->with([
                        'error' => 'La página venció. Probá de nuevo.',
                    ]);
                }
            }

            return $response;
        });
    })->create();
