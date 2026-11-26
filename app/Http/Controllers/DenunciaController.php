<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDenunciaRequest;
use App\Http\Requests\UpdateDenunciaRequest;
use App\Mail\NuevaAlertaMail;
use App\Models\Alerta;
use App\Models\Denuncia;
use App\Models\Publicacion;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class DenunciaController extends Controller
{
    /**
     * Display a listing of complaints for administration.
     */
    public function index(Request $request): Response
    {
        $estado = $request->query('estado', Denuncia::ESTADO_PENDIENTE);

        $denuncias = Denuncia::query()
            ->when($estado, function ($query, $estado) {
                return $query->where('estado', $estado);
            })
            ->with([
                'usuario:id,name,email',
                'publicacion:id,titulo,usuario_id',
                'publicacion.usuario:id,name,email',
            ])
            ->latest()
            ->get();

        return Inertia::render('Admin/Denuncias/Index', [
            'denuncias' => $denuncias,
            'estado_actual' => $estado,
        ]);
    }

    /**
     * Store a newly created complaint in storage.
     */
    public function store(StoreDenunciaRequest $request, Publicacion $publicacion): RedirectResponse
    {
        $publicacion->denuncias()->create([
            ...$request->validated(),
            'usuario_id' => $request->user()->id,
            'estado' => Denuncia::ESTADO_PENDIENTE,
        ]);

        return redirect()->route('publicaciones.show', $publicacion)
            ->with('success', 'Denuncia enviada exitosamente.');
    }

    /**
     * Update the specified complaint's status in storage.
     */
    public function update(UpdateDenunciaRequest $request, Denuncia $denuncia): RedirectResponse
    {
        $denuncia->update($request->validated());

        // Generar alertas ante la resolución de la denuncia (KAN-73)
        $estado = $denuncia->estado;
        if (in_array($estado, [Denuncia::ESTADO_REVISADA, Denuncia::ESTADO_DESCARTADA])) {
            $tituloPublicacion = $denuncia->publicacion?->titulo ?? 'tu publicación';

            // Alerta al denunciante
            $alertaDenunciante = Alerta::create([
                'usuario_id' => $denuncia->usuario_id,
                'publicacion_id' => $denuncia->publicacion_id,
                'tipo' => 'denuncia_resuelta',
                'mensaje' => "Tu denuncia sobre la publicación '{$tituloPublicacion}' fue marcada como {$estado}.",
                'leida' => false,
            ]);

            $denunciante = $denuncia->usuario;
            if ($denunciante && ($denunciante->notificar_por_mail ?? true)) {
                Mail::to($denunciante)->send(new NuevaAlertaMail($alertaDenunciante));
            }

            // Enviar push notification al denunciante si tiene FCM token (KAN-187)
            if ($denunciante) {
                app(PushNotificationService::class)->sendPush($denunciante, $alertaDenunciante);
            }

            // Alerta al dueño de la publicación denunciada
            if ($denuncia->publicacion && $denuncia->publicacion->usuario_id) {
                $alertaDueno = Alerta::create([
                    'usuario_id' => $denuncia->publicacion->usuario_id,
                    'publicacion_id' => $denuncia->publicacion_id,
                    'tipo' => 'denuncia_resuelta',
                    'mensaje' => "La denuncia sobre tu publicación '{$tituloPublicacion}' fue marcada como {$estado}.",
                    'leida' => false,
                ]);

                $dueno = $denuncia->publicacion->usuario;
                if ($dueno && ($dueno->notificar_por_mail ?? true)) {
                    Mail::to($dueno)->send(new NuevaAlertaMail($alertaDueno));
                }

                // Enviar push notification al dueño si tiene FCM token (KAN-187)
                if ($dueno) {
                    app(PushNotificationService::class)->sendPush($dueno, $alertaDueno);
                }
            }
        }

        return redirect()->route('admin.denuncias.index')
            ->with('success', 'Estado de la denuncia actualizado exitosamente.');
    }
}
