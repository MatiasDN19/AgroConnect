<?php

namespace App\Models;

use Database\Factories\DenunciaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'publicacion_id', 'motivo', 'descripcion', 'estado'])]
class Denuncia extends Model
{
    /** @use HasFactory<DenunciaFactory> */
    use HasFactory;

    protected $table = 'denuncias';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_REVISADA = 'revisada';

    public const ESTADO_DESCARTADA = 'descartada';

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
