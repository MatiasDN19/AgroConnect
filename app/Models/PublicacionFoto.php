<?php

namespace App\Models;

use Database\Factories\PublicacionFotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'publicacion_id',
    'ruta',
    'orden',
])]
class PublicacionFoto extends Model
{
    /** @use HasFactory<PublicacionFotoFactory> */
    use HasFactory;

    protected $table = 'publicacion_fotos';

    protected $appends = ['url'];

    /**
     * Get the full URL to the photo.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->ruta);
    }

    /**
     * @return BelongsTo<Publicacion, $this>
     */
    public function publicacion(): BelongsTo
    {
        return $this->belongsTo(Publicacion::class, 'publicacion_id');
    }

    /**
     * Delete the associated file from disk when model is deleted.
     */
    protected static function booted(): void
    {
        static::deleted(function (PublicacionFoto $foto) {
            Storage::disk('public')->delete($foto->ruta);
        });
    }
}
