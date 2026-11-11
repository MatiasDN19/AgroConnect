<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'usuario_id',
    'publicacion_id',
])]
class Favorito extends Model
{
    use HasFactory;

    // Explícito para evitar pluralización irregular
    protected $table = 'favoritos';

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * @return BelongsTo<Publicacion, $this>
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }
}
