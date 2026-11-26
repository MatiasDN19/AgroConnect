<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferenciaMensual extends Model
{
    use HasFactory;

    protected $table = 'referencias_mensuales';

    protected $fillable = [
        'mes',
        'producto',
        'categoria_id',
        'unidad',
        'precio_referencia',
        'cantidad_plan_nutrir',
        'nota',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mes' => 'date:Y-m-d',
            'precio_referencia' => 'decimal:2',
            'cantidad_plan_nutrir' => 'decimal:2',
        ];
    }

    /**
     * Relación con la categoría del producto.
     *
     * @return BelongsTo<Categoria, $this>
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Scope para filtrar por un mes específico (fecha o string Y-m).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeDelMes(Builder $query, string|Carbon $fecha): void
    {
        $inicioMes = Carbon::parse($fecha)->startOfMonth()->toDateString();
        $query->where('mes', $inicioMes);
    }

    /**
     * Scope para filtrar las referencias vigentes (mes actual).
     *
     * @param  Builder<$this>  $query
     */
    public function scopeVigentes(Builder $query): void
    {
        $query->delMes(now());
    }
}
