<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AlertaResource;
use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AlertaController extends Controller
{
    /**
     * Listar las alertas del usuario autenticado ordenadas por fecha descendente.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $alertas = $request->user()
            ->alertas()
            ->latest()
            ->get();

        return AlertaResource::collection($alertas);
    }

    /**
     * Marcar una alerta como leída.
     */
    public function marcarLeida(Request $request, Alerta $alerta): AlertaResource
    {
        abort_if($alerta->usuario_id !== $request->user()->id, 403, 'No tenés permiso para modificar esta alerta.');

        $alerta->update([
            'leida' => true,
        ]);

        return new AlertaResource($alerta);
    }

    /**
     * Marcar todas las alertas no leídas del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $marcadas = $request->user()
            ->alertas()
            ->where('leida', false)
            ->update([
                'leida' => true,
            ]);

        return response()->json([
            'marcadas' => $marcadas,
        ]);
    }
}
