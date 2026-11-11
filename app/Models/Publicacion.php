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

    public function puntuacionPromedio(): ?float
    {
        return $this->resenas()->avg('puntuacion');
    }

    /**
     * Calcula el precio promedio sugerido para una categoría basado en publicaciones activas.
     * Retorna null si no se alcanza el mínimo de publicaciones representativas (por defecto 3).
     */
    public static function calcularPrecioPromedioPorCategoria(int $categoriaId, int $minimoPublicaciones = 3): ?float
    {
        $query = static::query()
            ->where('categoria_id', $categoriaId)
            ->where('estado', static::ESTADO_ACTIVA);

        if ($query->count() < $minimoPublicaciones) {
            return null;
        }

        $promedio = $query->avg('precio');

        return $promedio !== null ? round((float) $promedio, 2) : null;
    }
}
