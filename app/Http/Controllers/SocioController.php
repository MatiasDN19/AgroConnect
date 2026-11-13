<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SocioController extends Controller
{
    /**
     * Listar solicitudes de socio pendientes para el panel admin.
     */
    public function index(Request $request): Response
    {
        $solicitudes = User::query()
            ->whereNotNull('numero_socio')
            ->where('socio_validado', false)
            ->latest('updated_at')
            ->get();

        return Inertia::render('Admin/Socios/Index', [
            'solicitudes' => $solicitudes,
        ]);
    }

    /**
     * Aceptar la solicitud de validación de un socio.
     */
    public function aceptar(Request $request, User $usuario): RedirectResponse
    {
        $usuario->update([
            'socio_validado' => true,
        ]);

        return back()->with('success', 'Solicitud de socio aceptada exitosamente.');
    }

    /**
     * Rechazar la solicitud de validación de un socio.
     */
    public function rechazar(Request $request, User $usuario): RedirectResponse
    {
        $usuario->update([
            'numero_socio' => null,
            'socio_validado' => false,
        ]);

        return back()->with('success', 'Solicitud de socio rechazada exitosamente.');
    }
}
