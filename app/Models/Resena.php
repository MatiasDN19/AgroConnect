<?php

namespace App\Models;

use Database\Factories\ResenaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'publicacion_id', 'puntuacion', 'comentario'])]
class Resena extends Model
{
    /** @use HasFactory<ResenaFactory> */
    use HasFactory;

    protected $table = 'resenas';

    protected function casts(): array
    {
        return [
            'puntuacion' => 'integer',
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
