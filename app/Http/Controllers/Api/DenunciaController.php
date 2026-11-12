<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDenunciaRequest;
use App\Http\Resources\DenunciaResource;
use App\Models\Denuncia;
use App\Models\Publicacion;
use Illuminate\Http\JsonResponse;

class DenunciaController extends Controller
{
    /**
     * Crear una denuncia contra una publicación.
     */
    public function store(StoreDenunciaRequest $request, ?Publicacion $publicacion = null): JsonResponse
    {
        $publicacionTarget = $publicacion ?? $request->getPublicacion();

        $denuncia = $publicacionTarget->denuncias()->create([
            'motivo' => $request->input('motivo'),
            'descripcion' => $request->input('descripcion'),
            'usuario_id' => $request->user()->id,
            'estado' => Denuncia::ESTADO_PENDIENTE,
        ]);

        return (new DenunciaResource($denuncia))
            ->response()
            ->setStatusCode(201);
    }
}
