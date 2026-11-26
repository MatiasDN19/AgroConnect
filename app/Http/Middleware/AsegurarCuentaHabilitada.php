<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AsegurarCuentaHabilitada
{
    /**
     * Intercepta peticiones de usuarios autenticados cuya cuenta fue inhabilitada,
     * cerrando su sesión de inmediato o revocando sus tokens de API.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->estaInhabilitado()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $user->currentAccessToken()?->delete();

                return response()->json([
                    'message' => 'Tu cuenta fue inhabilitada.',
                    'code' => 'cuenta_inhabilitada',
                ], 403);
            }

            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->with('error', 'Tu cuenta fue inhabilitada. Si creés que es un error, escribinos.');
        }

        return $next($request);
    }
}
