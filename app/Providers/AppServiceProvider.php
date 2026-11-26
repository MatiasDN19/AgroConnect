<?php

namespace App\Providers;

use App\Models\Publicacion;
use App\Observers\PublicacionObserver;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
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
    }
}
