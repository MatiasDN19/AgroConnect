<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResenaRequest;
use App\Http\Resources\ResenaResource;
use App\Models\Publicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResenaController extends Controller
{
    /**
     * Listar reseñas de una publicación.
     */
    public function index(Publicacion $publicacion): AnonymousResourceCollection
    {
        $resenas = $publicacion->resenas()
            ->with('usuario:id,name')
            ->latest()
            ->get();

        return ResenaResource::collection($resenas);
    }

    /**
     * Crear una reseña en una publicación.
     */
    public function store(StoreResenaRequest $request, Publicacion $publicacion): JsonResponse
    {
        $resena = $publicacion->resenas()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        $resena->load('usuario:id,name');

        return (new ResenaResource($resena))
            ->response()
            ->setStatusCode(201);
    }
}
