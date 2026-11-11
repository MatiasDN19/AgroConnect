<?php

namespace App\Policies;

use App\Models\SolicitudCompra;
use App\Models\User;

class SolicitudCompraPolicy
{
    /**
     * Determina si el usuario puede ver la solicitud.
     * Solo el comprador que la creó, el dueño de la publicación o un admin.
     */
    public function view(User $user, SolicitudCompra $solicitud): bool
    {
        return $user->esAdmin()
            || $user->id === $solicitud->usuario_id
            || $user->id === $solicitud->publicacion->usuario_id;
    }

    /**
     * Determina si el usuario puede resolver (aceptar o rechazar) la solicitud.
     * Solo el dueño de la publicación o un admin.
     */
    public function resolve(User $user, SolicitudCompra $solicitud): bool
    {
        return $user->esAdmin() || $user->id === $solicitud->publicacion->usuario_id;
    }
}
