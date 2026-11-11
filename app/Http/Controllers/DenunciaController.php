<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDenunciaRequest;
use App\Models\Denuncia;
use App\Models\Publicacion;
use Illuminate\Http\RedirectResponse;

class DenunciaController extends Controller
{
    /**
     * Store a newly created complaint in storage.
     */
    public function store(StoreDenunciaRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->denuncias()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
            'estado' => Denuncia::ESTADO_PENDIENTE,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Denuncia enviada exitosamente.');
    }
}
