<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\CuentaSocial;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SocialController extends Controller
{
    private const PROVEEDORES_PERMITIDOS = ['google', 'facebook', 'apple'];

    /**
     * Redirigir al proveedor OAuth.
     */
    public function redirigir(string $proveedor): RedirectResponse|SymfonyRedirectResponse
    {
        abort_if(! in_array($proveedor, self::PROVEEDORES_PERMITIDOS), 404);

        return Socialite::driver($proveedor)->redirect();
    }

    /**
     * Procesar el callback del proveedor OAuth.
     */
    public function callback(string $proveedor): RedirectResponse
    {
        abort_if(! in_array($proveedor, self::PROVEEDORES_PERMITIDOS), 404);

        try {
            $socialUser = Socialite::driver($proveedor)->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'No se pudo autenticar con '.ucfirst($proveedor).'. Por favor intentá nuevamente.',
            ]);
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors([
                'email' => 'La cuenta de '.ucfirst($proveedor).' no tiene un correo electrónico asociado.',
            ]);
        }

        $cuentaSocial = CuentaSocial::where('proveedor', $proveedor)
            ->where('proveedor_id', $socialUser->getId())
            ->first();

        $esNuevo = false;

        if ($cuentaSocial) {
            $user = $cuentaSocial->usuario;
        } else {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->cuentasSociales()->create([
                    'proveedor' => $proveedor,
                    'proveedor_id' => $socialUser->getId(),
                ]);
            } else {
                $user = User::create([
                    'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'Usuario',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'email_verified_at' => now(),
                    'rol' => User::ROL_PRODUCTOR,
                ]);

                $user->cuentasSociales()->create([
                    'proveedor' => $proveedor,
                    'proveedor_id' => $socialUser->getId(),
                ]);

                $esNuevo = true;
            }
        }

        if ($user->estaInhabilitado()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta fue inhabilitada, contactá al administrador.',
            ]);
        }

        Auth::login($user);
        request()->session()->regenerate();

        if ($esNuevo) {
            return redirect()->route('rol.elegir');
        }

        return redirect()->intended(route('publicaciones.index', absolute: false));
    }

    /**
     * Mostrar la vista para elegir rol en el primer ingreso con red social.
     */
    public function mostrarElegirRol(): Response
    {
        return Inertia::render('Auth/ElegirRol');
    }

    /**
     * Guardar el rol seleccionado por el usuario.
     */
    public function guardarRol(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rol' => ['required', 'string', Rule::in([User::ROL_PRODUCTOR, User::ROL_COMPRADOR])],
        ]);

        $request->user()->update([
            'rol' => $validated['rol'],
        ]);

        return redirect()->route('publicaciones.index');
    }
}
