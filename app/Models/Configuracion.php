<?php

namespace App\Models;

use Database\Factories\ConfiguracionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable([
    'clave',
    'valor',
])]
class Configuracion extends Model
{
    /** @use HasFactory<ConfiguracionFactory> */
    use HasFactory;

    protected $table = 'configuraciones';

    /**
     * Cache key prefix.
     */
    public const CACHE_PREFIX = 'configuracion_';

    /**
     * Obtener el valor de una configuración con fallback al archivo config/agroconnect.php o valor por defecto.
     */
    public static function obtener(string $clave, mixed $porDefecto = null): mixed
    {
        $defaultConfig = config("agroconnect.{$clave}", $porDefecto);

        return Cache::rememberForever(self::CACHE_PREFIX.$clave, function () use ($clave, $defaultConfig) {
            $registro = static::where('clave', $clave)->first();

            return $registro !== null && $registro->valor !== null
                ? $registro->valor
                : $defaultConfig;
        });
    }

    /**
     * Guardar o actualizar una configuración y limpiar su caché.
     */
    public static function fijar(string $clave, mixed $valor): static
    {
        $registro = static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor]
        );

        Cache::forget(self::CACHE_PREFIX.$clave);

        return $registro;
    }

    /**
     * Limpiar la caché automáticamente al guardar o eliminar.
     */
    protected static function booted(): void
    {
        static::saved(function (Configuracion $configuracion) {
            Cache::forget(self::CACHE_PREFIX.$configuracion->clave);
        });

        static::deleted(function (Configuracion $configuracion) {
            Cache::forget(self::CACHE_PREFIX.$configuracion->clave);
        });
    }
}
