<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
