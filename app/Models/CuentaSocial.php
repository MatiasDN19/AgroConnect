<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaSocial extends Model
{
    use HasFactory;

    protected $table = 'cuentas_sociales';

    protected $fillable = [
        'usuario_id',
        'proveedor',
        'proveedor_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
