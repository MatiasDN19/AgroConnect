<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarRolUsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UsuarioController extends Controller
{
    /**
     * Alternar el estado de inhabilitación de un usuario.
     */
    public function toggleInhabilitado(Request $request, User $usuario): RedirectResponse
    {
        if ($request->user()->id === $usuario->id) {
            abort(403, 'No podés inhabilitar tu propia cuenta de administrador.');
        }

        $nuevoEstado = $request->has('inhabilitado')
            ? $request->boolean('inhabilitado')
            : ! $usuario->estaInhabilitado();

        $usuario->update([
            'inhabilitado' => $nuevoEstado,
        ]);

        $mensaje = $usuario->estaInhabilitado()
            ? 'Usuario inhabilitado correctamente.'
            : 'Usuario reactivado correctamente.';

        return back()->with('success', $mensaje);
    }

    /**
     * Cambiar el rol de un usuario del sistema.
     */
    public function cambiarRol(CambiarRolUsuarioRequest $request, User $usuario): RedirectResponse
    {
        if ($request->user()->id === $usuario->id) {
            abort(403, 'No podés modificar tu propio rol de administrador.');
        }

        $nuevoRol = $request->validated('rol');

        // Si se intenta quitar el rol de administrador a un admin, asegurar que no sea el único
        if ($usuario->esAdmin() && $nuevoRol !== User::ROL_ADMIN) {
            $otrosAdmins = User::where('rol', User::ROL_ADMIN)
                ->where('id', '!=', $usuario->id)
                ->count();

            if ($otrosAdmins === 0) {
                return back()->with('error', 'No se puede quitar el rol al único administrador de la plataforma.');
            }
        }

        $usuario->update([
            'rol' => $nuevoRol,
        ]);

        return back()->with('success', 'Rol de usuario actualizado correctamente.');
    }
}
