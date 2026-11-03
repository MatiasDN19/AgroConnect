<?php

namespace App\Policies;

use App\Models\Publicacion;
use App\Models\User;

class PublicacionPolicy
{
    /**
     * Cualquiera autenticado puede ver el listado (se filtra en el controller).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * El detalle de una publicación es público para cualquier usuario autenticado.
     */
    public function view(User $user, Publicacion $publicacion): bool
    {
        return true;
    }

    /**
     * Solo los productores pueden crear publicaciones (los admin administran, no publican).
     */
    public function create(User $user): bool
    {
        return $user->esProductor();
    }

    /**
     * Solo el dueño de la publicación (o un admin) puede editarla.
     */
    public function update(User $user, Publicacion $publicacion): bool
    {
        return $user->esAdmin() || $user->id === $publicacion->usuario_id;
    }

    /**
     * Solo el dueño de la publicación (o un admin) puede eliminarla.
     */
    public function delete(User $user, Publicacion $publicacion): bool
    {
        return $user->esAdmin() || $user->id === $publicacion->usuario_id;
    }
}
