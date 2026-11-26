<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResenaRequest;
use App\Mail\NuevaAlertaMail;
use App\Models\Alerta;
use App\Models\Publicacion;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ResenaController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(StoreResenaRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->resenas()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
        ]);

        // Generar alerta para el dueño de la publicación (KAN-73)
        $alerta = Alerta::create([
            'usuario_id' => $publicacion->usuario_id,
            'publicacion_id' => $publicacion->id,
            'tipo' => 'nueva_resena',
            'mensaje' => "Tu publicación '{$publicacion->titulo}' recibió una nueva reseña.",
            'leida' => false,
        ]);

        // Enviar mail si el usuario no desactivó notificaciones (KAN-76)
        $dueno = $publicacion->usuario;
        if ($dueno && ($dueno->notificar_por_mail ?? true)) {
            Mail::to($dueno)->send(new NuevaAlertaMail($alerta));
        }

        // Enviar push notification si tiene token FCM registrado (KAN-187)
        if ($dueno) {
            app(PushNotificationService::class)->sendPush($dueno, $alerta);
        }

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Reseña creada exitosamente.');
    }
}
