<?php

namespace App\Http\Middleware;

use App\Models\Configuracion;
use App\Models\Denuncia;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Mensajes de una sola vez que mandan los controladores con ->with('success'|'error', ...)
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Datos del panel de administración (solo para administradores)
            'admin' => fn () => $request->user()?->esAdmin()
                ? ['denuncias_pendientes' => Denuncia::where('estado', Denuncia::ESTADO_PENDIENTE)->count()]
                : null,
            // Datos públicos de contacto y redes de la plataforma
            'contacto' => fn () => [
                'email' => Configuracion::obtener('contacto_email'),
                'whatsapp' => Configuracion::obtener('contacto_whatsapp'),
                'direccion' => Configuracion::obtener('contacto_direccion'),
                'facebook' => Configuracion::obtener('red_facebook'),
                'instagram' => Configuracion::obtener('red_instagram'),
            ],
        ];
    }
}
