<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublicacionRequest;
use App\Models\Categoria;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
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
    public function show(Publicacion $publicacion): Response
    {
        // El detalle completo con Inertia se implementa en KAN-55.
        return response()->noContent();
    }
}
