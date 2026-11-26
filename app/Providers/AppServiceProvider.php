<?php

namespace App\Providers;

use App\Models\Publicacion;
use App\Observers\PublicacionObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        Publicacion::observe(PublicacionObserver::class);

        Password::defaults(function () {
            $rule = Password::min(8)->letters()->numbers();

            return app()->environment('testing') ? $rule : $rule->uncompromised();
        });

        $buildRateLimitResponse = function (Request $request, array $headers) {
            $seconds = $headers['Retry-After'] ?? 60;
            $mensaje = "Demasiados intentos. Probá de nuevo en {$seconds} segundos.";

            if ($request->is('api/*') || $request->expectsJson() || $request->header('X-Inertia')) {
                return response()->json([
                    'message' => $mensaje,
                    'code' => 'demasiados_intentos',
                    'errors' => [
                        'email' => [$mensaje],
                        'general' => [$mensaje],
                    ],
                ], 429, $headers);
            }

            return response($mensaje, 429, $headers);
        };

        RateLimiter::for('api-login', function (Request $request) use ($buildRateLimitResponse) {
            $email = Str::lower($request->input('email', ''));

            return Limit::perMinute(5)
                ->by($email.'|'.$request->ip())
                ->response($buildRateLimitResponse);
        });

        RateLimiter::for('api-register', function (Request $request) use ($buildRateLimitResponse) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response($buildRateLimitResponse);
        });

        RateLimiter::for('web-register', function (Request $request) use ($buildRateLimitResponse) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response($buildRateLimitResponse);
        });

        RateLimiter::for('envios', function (Request $request) use ($buildRateLimitResponse) {
            $key = $request->user()?->id ? 'user:'.$request->user()->id : 'ip:'.$request->ip();

            return Limit::perMinute(10)
                ->by($key)
                ->response($buildRateLimitResponse);
        });
    }
}
