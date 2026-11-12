<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CambiarEstadoPublicacionRequest;
use App\Http\Requests\StorePublicacionRequest;
use App\Http\Requests\UpdatePublicacionRequest;
use App\Http\Resources\PublicacionResource;
use App\Models\Publicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PublicacionController extends Controller
{
    /**
     * Listar publicaciones activas con soporte de filtro por categoría y orden por distancia.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $hasUbicacion = $request->filled('lat') && $request->filled('lng');

        $query = Publicacion::query()
            ->where('publicaciones.estado', Publicacion::ESTADO_ACTIVA)
            ->when($request->filled('categoria_id'), function ($query) use ($request) {
                $query->where('publicaciones.categoria_id', $request->input('categoria_id'));
            });

        if ($hasUbicacion) {
            $userLat = (float) $request->input('lat');
            $userLng = (float) $request->input('lng');

            $cosFormula = "cos(radians({$userLat})) * cos(radians(users.lat)) * cos(radians(users.lng) - radians({$userLng})) + sin(radians({$userLat})) * sin(radians(users.lat))";

            $clamped = match (DB::connection()->getDriverName()) {
                'mysql', 'mariadb' => "LEAST(1.0, GREATEST(-1.0, {$cosFormula}))",
                default => "min(1.0, max(-1.0, {$cosFormula}))",
            };

            $haversine = "(6371 * acos({$clamped}))";

            $query->join('users', 'publicaciones.usuario_id', '=', 'users.id')
                ->select('publicaciones.*')
                ->selectRaw("{$haversine} as distancia")
                ->orderByRaw('CASE WHEN users.lat IS NOT NULL AND users.lng IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw("{$haversine} ASC")
                ->orderBy('publicaciones.created_at', 'desc');
        } else {
            $query->latest('publicaciones.created_at');
        }

        $publicaciones = $query
            ->with(['categoria:id,nombre', 'usuario:id,name,telefono,lat,lng'])
            ->withAvg('resenas as puntuacion_promedio', 'puntuacion')
            ->get();

        return PublicacionResource::collection($publicaciones);
    }

    /**
     * Detalle de una publicación específica.
     */
    public function show(Publicacion $publicacion): PublicacionResource
    {
        $publicacion->load([
            'usuario:id,name,email,telefono,lat,lng',
            'categoria:id,nombre,descripcion',
            'resenas.usuario:id,name',
        ]);

        return new PublicacionResource($publicacion);
    }

    /**
     * Crear una nueva publicación (autorización delegada a StorePublicacionRequest).
     */
    public function store(StorePublicacionRequest $request): JsonResponse
    {
        $publicacion = Publicacion::create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        $publicacion->load(['categoria:id,nombre', 'usuario:id,name,telefono,lat,lng']);

        return (new PublicacionResource($publicacion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Actualizar una publicación existente (autorización delegada a UpdatePublicacionRequest).
     */
    public function update(UpdatePublicacionRequest $request, Publicacion $publicacion): PublicacionResource
    {
        $publicacion->update($request->validated());

        $publicacion->load(['categoria:id,nombre', 'usuario:id,name,telefono,lat,lng']);

        return new PublicacionResource($publicacion);
    }

    /**
     * Cambiar el estado de una publicación (autorización delegada a CambiarEstadoPublicacionRequest).
     */
    public function cambiarEstado(CambiarEstadoPublicacionRequest $request, Publicacion $publicacion): PublicacionResource
    {
        $publicacion->update($request->validated());

        return new PublicacionResource($publicacion);
    }
}
