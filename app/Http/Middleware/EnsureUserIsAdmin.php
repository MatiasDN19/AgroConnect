<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe el acceso a rutas del panel de administración a usuarios
 * con rol "admin". Se registra con el alias `admin` en bootstrap/app.php
 * y se aplica a nivel de grupo de rutas (ver routes/admin.php).
 */
class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->esAdmin()) {
            abort(403, 'No tenés permisos para acceder al panel de administración.');
        }

        return $next($request);
    }
}
