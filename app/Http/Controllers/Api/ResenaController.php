<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreResenaRequest;
use App\Http\Resources\ResenaResource;
use App\Mail\NuevaAlertaMail;
use App\Models\Alerta;
use App\Models\Publicacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Mail;

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
        $publicacion->resenas()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        // Generar alerta para el dueño de la publicación (KAN-73)
        $alerta = Alerta::create([
            'usuario_id' => $publicacion->usuario_id,
            'tipo' => 'nueva_resena',
            'mensaje' => "Tu publicación '{$publicacion->titulo}' recibió una nueva reseña.",
            'leida' => false,
        ]);

        // Enviar mail si el usuario no desactivó notificaciones (KAN-76)
        $dueno = $publicacion->usuario;
        if ($dueno && ($dueno->notificar_por_mail ?? true)) {
            Mail::to($dueno)->send(new NuevaAlertaMail($alerta));
        }

        $resena = $publicacion->resenas()->latest()->first();

        $resena->load('usuario:id,name');

        return (new ResenaResource($resena))
            ->response()
            ->setStatusCode(201);
    }
}
