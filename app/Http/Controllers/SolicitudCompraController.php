<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResolveSolicitudCompraRequest;
use App\Http\Requests\StoreSolicitudCompraRequest;
use App\Mail\NuevaAlertaMail;
use App\Models\Alerta;
use App\Models\Publicacion;
use App\Models\SolicitudCompra;
use App\Models\User;
use App\Services\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SolicitudCompraController extends Controller
{
    /**
     * Listar las solicitudes de compra recibidas sobre publicaciones del productor autenticado.
     */
    public function index(Request $request): InertiaResponse
    {
        $solicitudes = SolicitudCompra::query()
            ->whereHas('publicacion', function ($q) use ($request) {
                $q->where('usuario_id', $request->user()->id);
            })
            ->with([
                'publicacion:id,titulo,precio,unidad',
                'usuario:id,name,email,telefono',
            ])
            ->latest()
            ->get()
            // El contacto del comprador (email y teléfono) solo viaja si la solicitud fue
            // aceptada (KAN-151): antes de aceptar, el productor ve nombre y mensaje.
            // Se arma campo por campo porque el eager loading comparte la misma instancia
            // de User entre solicitudes del mismo comprador (makeHidden afectaría a todas).
            ->map(fn (SolicitudCompra $solicitud) => [
                ...$solicitud->only(['id', 'estado', 'cantidad', 'mensaje', 'created_at', 'updated_at']),
                'publicacion' => $solicitud->publicacion?->only(['id', 'titulo', 'precio', 'unidad']),
                'usuario' => $solicitud->usuario?->only(
                    $solicitud->estado === SolicitudCompra::ESTADO_ACEPTADA
                        ? ['id', 'name', 'email', 'telefono']
                        : ['id', 'name']
                ),
            ]);

        return Inertia::render('SolicitudesCompra/Index', [
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Listar las solicitudes de compra enviadas por el comprador autenticado (KAN-263).
     */
    public function enviadas(Request $request): InertiaResponse
    {
        $solicitudes = SolicitudCompra::query()
            ->where('usuario_id', $request->user()->id)
            ->with([
                'publicacion' => function ($q) {
                    $q->select('id', 'usuario_id', 'titulo', 'unidad')
                        ->with([
                            'usuario:id,name,telefono,socio_validado',
                            'fotos' => fn ($f) => $f->orderBy('orden'),
                        ]);
                },
            ])
            ->latest('created_at')
            ->latest('id')
            ->paginate(15)
            ->through(function (SolicitudCompra $solicitud) {
                $productor = $solicitud->publicacion?->usuario;
                $esAceptada = $solicitud->estado === SolicitudCompra::ESTADO_ACEPTADA;

                return [
                    ...$solicitud->only(['id', 'estado', 'cantidad', 'mensaje', 'created_at', 'updated_at']),
                    'publicacion' => [
                        'id' => $solicitud->publicacion?->id,
                        'titulo' => $solicitud->publicacion?->titulo,
                        'unidad' => $solicitud->publicacion?->unidad,
                        'portada' => $solicitud->publicacion?->fotos?->first()?->url,
                        'fotos' => $solicitud->publicacion?->fotos,
                    ],
                    'productor' => $productor ? [
                        'id' => $productor->id,
                        'name' => $productor->name,
                        'socio_validado' => (bool) $productor->socio_validado,
                        ...($esAceptada ? ['telefono' => $productor->telefono] : []),
                    ] : null,
                ];
            });

        return Inertia::render('SolicitudesCompra/Enviadas', [
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Store a newly created purchase request for the given publication.
     */
    public function store(StoreSolicitudCompraRequest $request, Publicacion $publicacion): RedirectResponse
    {
        SolicitudCompra::create([
            'usuario_id' => $request->user()->id,
            'publicacion_id' => $publicacion->id,
            'cantidad' => $request->validated('cantidad'),
            'mensaje' => $request->validated('mensaje'),
            'estado' => SolicitudCompra::ESTADO_PENDIENTE,
        ]);

        // Generar alerta para el productor dueño de la publicación (KAN-250)
        $productor = $publicacion->usuario;
        if ($productor) {
            $this->notificarUsuario(
                $productor,
                'nueva_solicitud_compra',
                "Recibiste una solicitud de compra por '{$publicacion->titulo}'.",
                $publicacion->id
            );
        }

        return back()->with('success', 'Solicitud de compra enviada exitosamente.');
    }

    /**
     * Resolver (aceptar o rechazar) una solicitud de compra recibida.
     * Solo se puede resolver si se encuentra en estado 'pendiente'.
     */
    public function resolve(ResolveSolicitudCompraRequest $request, SolicitudCompra $solicitud): RedirectResponse
    {
        if ($solicitud->estado !== SolicitudCompra::ESTADO_PENDIENTE) {
            return back()->withErrors([
                'estado' => 'Solo se pueden responder solicitudes en estado pendiente.',
            ]);
        }

        $nuevoEstado = $request->validated('estado');

        if ($nuevoEstado === SolicitudCompra::ESTADO_ACEPTADA) {
            $resultado = DB::transaction(function () use ($solicitud, $nuevoEstado) {
                // Bloquear la fila de la publicación para control de concurrencia de stock
                /** @var Publicacion|null $publicacion */
                $publicacion = Publicacion::query()
                    ->where('id', $solicitud->publicacion_id)
                    ->lockForUpdate()
                    ->first();

                if (! $publicacion || $publicacion->stock < $solicitud->cantidad) {
                    return false;
                }

                // Descontar stock usando el modelo para que dispare el PublicacionObserver (KAN-180)
                $publicacion->stock -= $solicitud->cantidad;
                $publicacion->save();

                $solicitud->update([
                    'estado' => $nuevoEstado,
                ]);

                return true;
            });

            if (! $resultado) {
                $stockActual = $solicitud->publicacion?->stock ?? 0;

                return back()->withErrors([
                    'estado' => "No hay stock suficiente: quedan {$stockActual}.",
                ]);
            }
        } else {
            // Rechazar no modifica el stock
            $solicitud->update([
                'estado' => $nuevoEstado,
            ]);
        }

        $accion = $solicitud->estado === SolicitudCompra::ESTADO_ACEPTADA ? 'aceptada' : 'rechazada';

        // Generar alerta para el comprador que realizó la solicitud (KAN-250)
        $comprador = $solicitud->usuario;
        $tituloPublicacion = $solicitud->publicacion?->titulo ?? 'la publicación';
        $tipoAlerta = $solicitud->estado === SolicitudCompra::ESTADO_ACEPTADA
            ? 'solicitud_compra_aceptada'
            : 'solicitud_compra_rechazada';

        if ($comprador) {
            $this->notificarUsuario(
                $comprador,
                $tipoAlerta,
                "Tu solicitud por '{$tituloPublicacion}' fue {$accion}.",
                $solicitud->publicacion_id
            );
        }

        return back()->with('success', "Solicitud de compra {$accion} exitosamente.");
    }

    /**
     * Notificar al usuario mediante alerta en el sistema, correo y notificación push.
     */
    private function notificarUsuario(User $usuario, string $tipo, string $mensaje, ?int $publicacionId = null): void
    {
        $alerta = Alerta::create([
            'usuario_id' => $usuario->id,
            'publicacion_id' => $publicacionId,
            'tipo' => $tipo,
            'mensaje' => $mensaje,
            'leida' => false,
        ]);

        if ($usuario->notificar_por_mail ?? true) {
            Mail::to($usuario)->send(new NuevaAlertaMail($alerta));
        }

        app(PushNotificationService::class)->sendPush($usuario, $alerta);
    }
}
