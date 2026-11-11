<?php

namespace App\Http\Controllers;

use App\Models\Favorito;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FavoritoController extends Controller
{
    /**
     * Listar las publicaciones favoritas del usuario autenticado.
     */
    public function index(Request $request): InertiaResponse
    {
        $favoritos = Favorito::query()
            ->where('usuario_id', $request->user()->id)
            ->with([
                'publicacion.categoria:id,nombre',
                'publicacion.usuario:id,name',
            ])
            ->latest()
            ->get();

        return Inertia::render('Favoritos/Index', [
            'favoritos' => $favoritos,
        ]);
    }

    /**
     * Agregar una publicación a favoritos.
     * Es idempotente mediante firstOrCreate.
     */
    public function store(Request $request, Publicacion $publicacion): RedirectResponse
    {
        Favorito::firstOrCreate([
            'usuario_id' => $request->user()->id,
            'publicacion_id' => $publicacion->id,
        ]);

        return back()->with('success', 'Publicación agregada a tus favoritos.');
    }

    /**
     * Quitar una publicación de favoritos.
     * Solo el dueño del favorito puede eliminarlo.
     */
    public function destroy(Request $request, Publicacion $publicacion): RedirectResponse
    {
        Favorito::query()
            ->where('usuario_id', $request->user()->id)
            ->where('publicacion_id', $publicacion->id)
            ->delete();

        return back()->with('success', 'Publicación eliminada de tus favoritos.');
    }
}
