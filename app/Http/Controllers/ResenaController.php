<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResenaRequest;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;

class ResenaController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(StoreResenaRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->resenas()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Reseña creada exitosamente.');
    }
}
