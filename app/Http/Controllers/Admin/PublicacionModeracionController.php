<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BloquearPublicacionRequest;
use App\Models\Categoria;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicacionModeracionController extends Controller
{
    /**
     * Listado de todas las publicaciones para moderación administrativa.
     */
    public function index(Request $request): Response
    {
        $publicaciones = Publicacion::query()
            ->with([
                'usuario:id,name,email',
                'categoria:id,nombre',
                'bloqueadaPor:id,name',
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(function ($q) use ($term) {
                    $q->where('titulo', 'like', $term)
                        ->orWhere('descripcion', 'like', $term);
                });
            })
            ->when($request->filled('estado'), function ($query) use ($request) {
                $query->where('estado', $request->input('estado'));
            })
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                $query->where('categoria_id', $request->input('categoria_id'));
            })
            ->latest('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Publicaciones/Index', [
            'publicaciones' => $publicaciones,
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
            'filtros' => $request->only(['q', 'estado', 'categoria_id']),
        ]);
    }

    /**
     * Bloquear una publicación por incumplimiento de normas.
     */
    public function bloquear(BloquearPublicacionRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update([
            'estado' => Publicacion::ESTADO_BLOQUEADA,
            'motivo_bloqueo' => $request->validated('motivo'),
            'bloqueada_por' => $request->user()->id,
            'bloqueada_at' => now(),
        ]);

        return back()->with('success', 'Publicación bloqueada exitosamente.');
    }

    /**
     * Desbloquear una publicación. Vuelve a estado pausada para que el productor decida cuándo reactivarla.
     */
    public function desbloquear(Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update([
            'estado' => Publicacion::ESTADO_PAUSADA,
            'motivo_bloqueo' => null,
            'bloqueada_por' => null,
            'bloqueada_at' => null,
        ]);

        return back()->with('success', 'Publicación desbloqueada correctamente (quedó en estado pausada).');
    }

    /**
     * Eliminar definitivamente una publicación.
     */
    public function destroy(Publicacion $publicacion): RedirectResponse
    {
        $publicacion->fotos->each->delete();
        $publicacion->delete();

        return back()->with('success', 'Publicación eliminada correctamente.');
    }
}
