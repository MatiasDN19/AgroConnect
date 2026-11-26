<?php

namespace App\Models;

use Database\Factories\AlertaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'publicacion_id', 'tipo', 'mensaje', 'leida'])]
class Alerta extends Model
{
    /** @use HasFactory<AlertaFactory> */
    use HasFactory;

    protected $table = 'alertas';

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** @return BelongsTo<Publicacion, $this> */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }
}
