<?php

namespace App\Http\Controllers;

use App\Models\Publicacion;
use App\Models\Resena;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProductorController extends Controller
{
    /**
     * Muestra el perfil público de un productor.
     */
    public function show(User $usuario): InertiaResponse
    {
        if (! $usuario->esProductor() || $usuario->estaInhabilitado()) {
            abort(404);
        }

        $publicaciones = $usuario->publicaciones()
            ->where('estado', Publicacion::ESTADO_ACTIVA)
            ->with([
                'categoria:id,nombre',
                'usuario:id,name,socio_validado',
                'fotos' => fn ($q) => $q->orderBy('orden')->limit(1),
            ])
            ->withAvg('resenas as puntuacion_promedio', 'puntuacion')
            ->latest('created_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $resenasStats = Resena::query()
            ->whereHas('publicacion', fn ($q) => $q->where('usuario_id', $usuario->id))
            ->selectRaw('COUNT(*) as total, AVG(puntuacion) as promedio')
            ->first();

        $cantidadResenas = (int) ($resenasStats?->total ?? 0);
        $promedioResenas = $cantidadResenas > 0 ? round((float) $resenasStats->promedio, 2) : null;

        return Inertia::render('Productores/Show', [
            'productor' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'socio_validado' => (bool) $usuario->socio_validado,
                'localidad' => $usuario->localidad,
                'created_at' => $usuario->created_at,
            ],
            'publicaciones' => $publicaciones,
            'puntuacion_promedio' => $promedioResenas,
            'promedio_resenas' => $promedioResenas,
            'cantidad_resenas' => $cantidadResenas,
        ]);
    }
}
