<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveSolicitudCompraRequest;
use App\Http\Requests\StoreSolicitudCompraRequest;
use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SolicitudCompraController extends Controller
{
    /**
     * Listar las solicitudes de compra recibidas sobre publicaciones del productor autenticado.
     */
    public function index(Request $request): InertiaResponse
    {
        $solicitudes = SolicitudCompra::query()
            ->whereHas('publicacion', function ($q) use ($request) {
                $q->where('usuario_id', $request->user()->id);
            })
            ->with([
                'publicacion:id,titulo,precio,unidad',
                'usuario:id,name,email,telefono',
            ])
            ->latest()
            ->get();

        return Inertia::render('SolicitudesCompra/Index', [
            'solicitudes' => $solicitudes,
        ]);
    }

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

    /**
     * Resolver (aceptar o rechazar) una solicitud de compra recibida.
     * Solo se puede resolver si se encuentra en estado 'pendiente'.
     */
    public function resolve(ResolveSolicitudCompraRequest $request, SolicitudCompra $solicitud): RedirectResponse
    {
        if ($solicitud->estado !== SolicitudCompra::ESTADO_PENDIENTE) {
            return back()->withErrors([
                'estado' => 'Solo se pueden responder solicitudes en estado pendiente.',
            ]);
        }

        $solicitud->update([
            'estado' => $request->validated('estado'),
        ]);

        $accion = $solicitud->estado === SolicitudCompra::ESTADO_ACEPTADA ? 'aceptada' : 'rechazada';

        return back()->with('success', "Solicitud de compra {$accion} exitosamente.");
    }
}
