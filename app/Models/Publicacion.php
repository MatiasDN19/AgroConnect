<?php

namespace App\Models;

use Database\Factories\PublicacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'usuario_id',
    'categoria_id',
    'titulo',
    'descripcion',
    'precio',
    'unidad',
    'stock',
    'estado',
])]
class Publicacion extends Model
{
    /** @use HasFactory<PublicacionFactory> */
    use HasFactory;

    // Explícito porque la pluralización automática de Eloquent (en inglés)
    // convertiría "Publicacion" en "publicacions" en vez de "publicaciones".
    protected $table = 'publicaciones';

    public const ESTADO_ACTIVA = 'activa';

    public const ESTADO_PAUSADA = 'pausada';

    public const ESTADO_VENDIDA = 'vendida';

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'stock' => 'integer',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** @return BelongsTo<Categoria, $this> */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /** @return HasMany<Resena, $this> */
    public function resenas(): HasMany
    {
        return $this->hasMany(Resena::class, 'publicacion_id');
    }

    /** @return HasMany<Denuncia, $this> */
    public function denuncias(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'publicacion_id');
    }

    /** @return HasMany<Favorito, $this> */
    public function favoritos(): HasMany
    {
        return $this->hasMany(Favorito::class, 'publicacion_id');
    }

    /** @return HasMany<SolicitudCompra, $this> */
    public function solicitudesCompra(): HasMany
    {
        return $this->hasMany(SolicitudCompra::class, 'publicacion_id');
    }

    /** @return HasMany<PublicacionFoto, $this> */
    public function fotos(): HasMany
    {
        return $this->hasMany(PublicacionFoto::class, 'publicacion_id')->orderBy('orden');
    }

    public function puntuacionPromedio(): ?float
    {
        return $this->resenas()->avg('puntuacion');
    }

    /**
     * Calcula el precio promedio sugerido para una categoría basado en publicaciones activas.
     * Retorna null si no se alcanza el mínimo de publicaciones representativas.
     */
    public static function calcularPrecioPromedioPorCategoria(int $categoriaId, ?int $minimoPublicaciones = null): ?float
    {
        $minimo = $minimoPublicaciones ?? (int) Configuracion::obtener('precio_sugerido_minimo', 3);

        return static::estadisticasPrecio($categoriaId, null, $minimo)['promedio'] ?? null;
    }

    /**
     * Referencia de precios de las publicaciones activas de una categoría (KAN-132).
     * Si se pasa la unidad, compara solo publicaciones con esa unidad (sin importar
     * mayúsculas ni espacios), para no mezclar precios por kg con precios por cajón.
     * Además del promedio devuelve mediana, mínimo y máximo: la mediana no se deja
     * arrastrar por un precio fuera de lo común y el rango muestra cuánto varía.
     * Devuelve null si hay menos publicaciones que el mínimo representativo.
     *
     * @return array{promedio: float, mediana: float, minimo: float, maximo: float, cantidad: int}|null
     */
    public static function estadisticasPrecio(int $categoriaId, ?string $unidad = null, ?int $minimoPublicaciones = null): ?array
    {
        $minimo = $minimoPublicaciones ?? (int) Configuracion::obtener('precio_sugerido_minimo', 3);
        $unidad = $unidad !== null ? mb_strtolower(trim($unidad)) : null;

        $precios = static::query()
            ->where('categoria_id', $categoriaId)
            ->where('estado', static::ESTADO_ACTIVA)
            ->when($unidad, fn ($query) => $query->whereRaw('LOWER(TRIM(unidad)) = ?', [$unidad]))
            ->orderBy('precio')
            ->pluck('precio')
            ->map(fn ($precio) => (float) $precio)
            ->values();

        $cantidad = $precios->count();

        if ($cantidad < $minimo) {
            return null;
        }

        // Con cantidad par, la mediana es el promedio de los dos del medio.
        $medio = intdiv($cantidad, 2);
        $mediana = $cantidad % 2 === 1
            ? $precios[$medio]
            : ($precios[$medio - 1] + $precios[$medio]) / 2;

        return [
            'promedio' => round($precios->avg(), 2),
            'mediana' => round($mediana, 2),
            'minimo' => $precios->first(),
            'maximo' => $precios->last(),
            'cantidad' => $cantidad,
        ];
    }
}
