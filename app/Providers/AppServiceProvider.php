<?php

namespace App\Providers;

use App\Models\Publicacion;
use App\Observers\PublicacionObserver;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

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

        // Habilitar funciones trigonométricas en SQLite para pruebas locales y CI con la fórmula de Haversine
        DB::beforeExecuting(function ($query, $bindings, $connection) {
            static $sqliteConfigured = false;

            if (! $sqliteConfigured && $connection instanceof SQLiteConnection) {
                $pdo = $connection->getPdo();
                $pdo->sqliteCreateFunction('cos', 'cos', 1);
                $pdo->sqliteCreateFunction('sin', 'sin', 1);
                $pdo->sqliteCreateFunction('acos', 'acos', 1);
                $pdo->sqliteCreateFunction('radians', fn ($deg) => deg2rad((float) $deg), 1);
                $sqliteConfigured = true;
            }
        });
    }
}
