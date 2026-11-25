<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /**
     * Listado paginado de usuarios para el panel admin.
     */
    public function index(Request $request): Response
    {
        $usuarios = User::query()
            ->select([
                'id',
                'name',
                'email',
                'rol',
                'telefono',
                'socio_validado',
                'inhabilitado',
                'created_at',
            ])
            ->withCount('publicaciones')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($request->filled('rol'), function ($query) use ($request) {
                $query->where('rol', $request->input('rol'));
            })
            ->when($request->filled('inhabilitado'), function ($query) use ($request) {
                $query->where('inhabilitado', (bool) $request->input('inhabilitado'));
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => $request->only(['q', 'rol', 'inhabilitado']),
        ]);
    }

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
