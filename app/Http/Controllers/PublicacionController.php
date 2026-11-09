<?php

namespace App\Http\Controllers;

use App\Http\Requests\CambiarEstadoPublicacionRequest;
use App\Http\Requests\StorePublicacionRequest;
use App\Http\Requests\UpdatePublicacionRequest;
use App\Models\Categoria;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PublicacionController extends Controller
{
    /**
     * Show the form for creating a new publication.
     * La autorización (solo productores) se aplica en la ruta con ->can('create', Publicacion::class).
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('Publicaciones/Create', [
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Store a newly created publication in storage.
     */
    public function store(StorePublicacionRequest $request): RedirectResponse
    {
        $publicacion = Publicacion::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Publicación creada exitosamente.');
    }

    /**
     * Display the specified publication.
     */
    public function show(Publicacion $publicacion): InertiaResponse
    {
        $publicacion->load([
            'usuario:id,name,email,telefono',
            'categoria:id,nombre',
            'resenas.usuario:id,name',
        ]);

        return Inertia::render('Publicaciones/Show', [
            'publicacion' => $publicacion,
            'puntuacion_promedio' => $publicacion->puntuacionPromedio(),
        ]);
    }

    /**
     * Show the form for editing the specified publication.
     * La autorización (dueño o admin) se aplica en la ruta con ->can('update', 'publicacion').
     */
    public function edit(Publicacion $publicacion): InertiaResponse
    {
        return Inertia::render('Publicaciones/Edit', [
            'publicacion' => $publicacion->only([
                'id', 'categoria_id', 'titulo', 'descripcion', 'precio', 'unidad', 'stock',
            ]),
            'categorias' => Categoria::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Update the specified publication in storage.
     */
    public function update(UpdatePublicacionRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update($request->validated());

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Publicación actualizada exitosamente.');
    }

    /**
     * Change the publication's state (activa, pausada, vendida).
     */
    public function cambiarEstado(CambiarEstadoPublicacionRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->update($request->validated());

        return back(fallback: route('publicaciones.show', $publicacion))
            ->with('success', 'Estado de la publicación actualizado exitosamente.');
    }
}
