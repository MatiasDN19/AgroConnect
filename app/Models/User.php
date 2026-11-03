<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol', 'telefono'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_PRODUCTOR = 'productor';

    public const ROL_ADMIN = 'admin';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->rol === self::ROL_ADMIN;
    }

    public function esProductor(): bool
    {
        return $this->rol === self::ROL_PRODUCTOR;
    }

    /** @return HasMany<Publicacion, $this> */
    public function publicaciones(): HasMany
    {
        return $this->hasMany(Publicacion::class, 'usuario_id');
    }

    /** @return HasMany<Resena, $this> */
    public function resenas(): HasMany
    {
        return $this->hasMany(Resena::class, 'usuario_id');
    }

    /** @return HasMany<Denuncia, $this> */
    public function denuncias(): HasMany
    {
        return $this->hasMany(Denuncia::class, 'usuario_id');
    }

    /** @return HasMany<Alerta, $this> */
    public function alertas(): HasMany
    {
        return $this->hasMany(Alerta::class, 'usuario_id');
    }
}
