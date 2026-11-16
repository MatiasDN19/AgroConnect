<?php

namespace App\Policies;

use App\Models\Alerta;
use App\Models\User;

class AlertaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->usuario_id;
    }

    /**
     * Determine whether the user can update the model (ej. marcar como leída).
     */
    public function update(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->usuario_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Alerta $alerta): bool
    {
        return $user->id === $alerta->usuario_id;
    }
}
