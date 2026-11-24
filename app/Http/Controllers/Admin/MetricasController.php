<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetricasController extends Controller
{
    /**
     * Devuelve las métricas básicas del panel de administración.
     * Soporta respuesta Inertia para la web y JsonResponse si se solicita JSON.
     */
    public function index(Request $request): Response|JsonResponse
    {
        $metricas = [
            'total_usuarios' => User::count(),
            'total_publicaciones_activas' => Publicacion::where('estado', Publicacion::ESTADO_ACTIVA)->count(),
            'total_denuncias_pendientes' => Denuncia::where('estado', Denuncia::ESTADO_PENDIENTE)->count(),
            'total_resenas' => Resena::count(),
        ];

        if ($request->wantsJson()) {
            return response()->json($metricas);
        }

        return Inertia::render('Admin/Metricas/Index', [
            // Para el resumen del panel se suman las solicitudes de socio sin validar
            // (misma consulta que el panel de Socios). La respuesta JSON no cambia. KAN-159.
            'metricas' => [
                ...$metricas,
                'total_socios_pendientes' => User::whereNotNull('numero_socio')
                    ->where('socio_validado', false)
                    ->count(),
            ],
            // Columna "últimas denuncias" del resumen (KAN-159): las 5 pendientes más
            // recientes, solo con lo que se muestra (motivo, fecha y publicación).
            'ultimas_denuncias' => Denuncia::query()
                ->where('estado', Denuncia::ESTADO_PENDIENTE)
                ->with('publicacion:id,titulo')
                ->latest()
                ->latest('id')
                ->limit(5)
                ->get(['id', 'publicacion_id', 'motivo', 'created_at']),
        ]);
    }
}
