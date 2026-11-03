<?php

namespace App\Policies;

use App\Models\Resena;
use App\Models\User;

class ResenaPolicy
{
    /**
     * Cualquier usuario autenticado puede reseñar una publicación
     * (que no sea la propia y que no la haya reseñado antes:
     * ver StoreResenaRequest).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Solo el autor de la reseña (o un admin) puede editarla.
     */
    public function update(User $user, Resena $resena): bool
    {
        return $user->esAdmin() || $user->id === $resena->usuario_id;
    }

    /**
     * Solo el autor de la reseña (o un admin) puede borrarla.
     */
    public function delete(User $user, Resena $resena): bool
    {
        return $user->esAdmin() || $user->id === $resena->usuario_id;
    }
}
