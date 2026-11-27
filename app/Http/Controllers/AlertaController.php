<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AlertaController extends Controller
{
    /**
     * Listar las alertas del usuario autenticado ordenadas por más recientes.
     * Soporta respuesta Inertia para la web y JSON si se solicita explícitamente.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $alertas = $request->user()
            ->alertas()
            ->latest()
            ->get();

        if ($request->wantsJson()) {
            return response()->json($alertas);
        }

        return Inertia::render('Alertas/Index', [
            'alertas' => $alertas,
        ]);
    }

    /**
     * Marcar una alerta como leída.
     */
    public function marcarLeida(Request $request, Alerta $alerta): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $alerta);

        $alerta->update([
            'leida' => true,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'alerta' => $alerta,
            ]);
        }

        return back()->with('success', 'Alerta marcada como leída.');
    }

    /**
     * Marcar todas las alertas no leídas del usuario como leídas.
     */
    public function marcarTodasLeidas(Request $request): RedirectResponse|JsonResponse
    {
        $marcadas = $request->user()
            ->alertas()
            ->where('leida', false)
            ->update([
                'leida' => true,
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'marcadas' => $marcadas,
            ]);
        }

        return back()->with('success', "Marcamos {$marcadas} notificaciones como leídas.");
    }
}
