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
            'metricas' => $metricas,
        ]);
    }
}
