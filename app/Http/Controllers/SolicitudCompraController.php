<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSolicitudCompraRequest;
use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use Illuminate\Http\RedirectResponse;

class SolicitudCompraController extends Controller
{
    /**
     * Store a newly created purchase request for the given publication.
     */
    public function store(StoreSolicitudCompraRequest $request, Publicacion $publicacion): RedirectResponse
    {
        SolicitudCompra::create([
            'usuario_id' => $request->user()->id,
            'publicacion_id' => $publicacion->id,
            'mensaje' => $request->validated('mensaje'),
            'estado' => SolicitudCompra::ESTADO_PENDIENTE,
        ]);

        return back()->with('success', 'Solicitud de compra enviada exitosamente.');
    }
}
