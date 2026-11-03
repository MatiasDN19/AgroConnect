<?php

namespace App\Policies;

use App\Models\Denuncia;
use App\Models\User;

class DenunciaPolicy
{
    /**
     * Solo el admin ve el listado completo de denuncias (en el panel de administración).
     */
    public function viewAny(User $user): bool
    {
        return $user->esAdmin();
    }

    public function view(User $user, Denuncia $denuncia): bool
    {
        return $user->esAdmin() || $user->id === $denuncia->usuario_id;
    }

    /**
     * Cualquier usuario autenticado puede denunciar una publicación
     * (la validación de que no sea la propia va en el Form Request).
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Solo el admin puede cambiar el estado de una denuncia (revisar/descartar).
     */
    public function update(User $user, Denuncia $denuncia): bool
    {
        return $user->esAdmin();
    }
}
